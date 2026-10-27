<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Source;
use App\Services\ScraperService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SourcesController extends Controller
{
    /**
     * GET /api/admin/sources
     * Danh sách nguồn tin tức (phân trang hoặc toàn bộ cho dropdown).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Source::query()
            ->with('category:id,name,slug')
            ->withCount('articles');

        // Tìm kiếm theo tên, slug, website_url, rss_url
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('website_url', 'like', "%{$search}%")
                  ->orWhere('rss_url', 'like', "%{$search}%");
            });
        }

        // Lọc theo danh mục mặc định
        if ($request->filled('category_id') && $request->input('category_id') !== 'all') {
            $query->where('category_id', $request->input('category_id'));
        }

        // Lọc theo trạng thái hoạt động
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $isActive = filter_var($request->input('status'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        // Lấy tất cả (phục vụ dropdown chọn nguồn tin của bài viết)
        if ($request->boolean('all')) {
            $sources = $query->orderBy('name', 'asc')->get();
            return response()->json([
                'success' => true,
                'data'    => $sources,
            ], 200);
        }

        $perPage = min((int) $request->input('per_page', 10), 100);
        $page = max((int) $request->input('page', 1), 1);
        $paginated = $query->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);

        $total = Source::count();
        $activeCount = Source::where('is_active', true)->count();

        return response()->json([
            'success' => true,
            'data'    => $paginated->items(),
            'meta'    => [
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'active_count' => $activeCount,
            ],
        ], 200);
    }

    /**
     * POST /api/admin/sources
     * Thêm mới một nguồn báo.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'slug'        => 'nullable|string|max:120|unique:sources,slug',
            'website_url' => 'required|url|max:255',
            'rss_url'     => 'nullable|url|max:500',
            'category_id' => 'nullable|exists:categories,id',
            'is_active'   => 'nullable|boolean',
        ], [
            'name.required'       => 'Tên nguồn báo không được để trống.',
            'name.max'            => 'Tên nguồn báo tối đa 100 ký tự.',
            'slug.unique'         => 'Đường dẫn (slug) nguồn báo đã tồn tại.',
            'website_url.required'=> 'URL trang web không được để trống.',
            'website_url.url'     => 'URL trang web không đúng định dạng URL.',
            'rss_url.url'         => 'URL RSS Feed không đúng định dạng URL.',
            'category_id.exists'  => 'Danh mục tin tức mặc định không tồn tại.',
        ]);

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        $originalSlug = $slug;
        $counter = 1;
        while (Source::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        $source = Source::create([
            'name'        => $validated['name'],
            'slug'        => $slug,
            'website_url' => $validated['website_url'],
            'rss_url'     => $validated['rss_url'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'is_active'   => $validated['is_active'] ?? true,
        ]);

        $source->load('category:id,name,slug');
        $source->loadCount('articles');

        return response()->json([
            'success' => true,
            'message' => "Đã tạo nguồn báo \"{$source->name}\" thành công!",
            'data'    => $source,
        ], 201);
    }

    /**
     * GET /api/admin/sources/{id}
     * Chi tiết một nguồn báo.
     */
    public function show(int $id): JsonResponse
    {
        $source = Source::with('category:id,name,slug')->withCount('articles')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $source,
        ], 200);
    }

    /**
     * PUT /api/admin/sources/{id}
     * Cập nhật thông tin nguồn báo.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $source = Source::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'slug'        => ['nullable', 'string', 'max:120', Rule::unique('sources', 'slug')->ignore($source->id)],
            'website_url' => 'required|url|max:255',
            'rss_url'     => 'nullable|url|max:500',
            'category_id' => 'nullable|exists:categories,id',
            'is_active'   => 'nullable|boolean',
        ], [
            'name.required'       => 'Tên nguồn báo không được để trống.',
            'name.max'            => 'Tên nguồn báo tối đa 100 ký tự.',
            'slug.unique'         => 'Đường dẫn (slug) nguồn báo đã tồn tại.',
            'website_url.required'=> 'URL trang web không được để trống.',
            'website_url.url'     => 'URL trang web không đúng định dạng URL.',
            'rss_url.url'         => 'URL RSS Feed không đúng định dạng URL.',
            'category_id.exists'  => 'Danh mục tin tức mặc định không tồn tại.',
        ]);

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);

        $source->update([
            'name'        => $validated['name'],
            'slug'        => $slug,
            'website_url' => $validated['website_url'],
            'rss_url'     => $validated['rss_url'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'is_active'   => $validated['is_active'] ?? $source->is_active,
        ]);

        $source->load('category:id,name,slug');
        $source->loadCount('articles');

        return response()->json([
            'success' => true,
            'message' => "Đã cập nhật nguồn báo \"{$source->name}\" thành công!",
            'data'    => $source,
        ], 200);
    }

    /**
     * PATCH /api/admin/sources/{id}/toggle-status
     * Bật / tắt trạng thái hoạt động của nguồn báo.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $source = Source::findOrFail($id);
        $source->is_active = !$source->is_active;
        $source->save();

        $statusText = $source->is_active ? 'kích hoạt hoạt động' : 'vô hiệu hóa';

        return response()->json([
            'success' => true,
            'message' => "Đã {$statusText} nguồn báo \"{$source->name}\".",
            'data'    => [
                'id'        => $source->id,
                'is_active' => $source->is_active,
            ],
        ], 200);
    }

    /**
     * DELETE /api/admin/sources/{id}
     * Xóa một nguồn báo.
     */
    public function destroy(int $id): JsonResponse
    {
        $source = Source::findOrFail($id);
        $sourceName = $source->name;

        // Xóa nguồn báo (các bài viết liên quan sẽ có source_id = null do nullOnDelete)
        $source->delete();

        return response()->json([
            'success' => true,
            'message' => "Đã xóa nguồn báo \"{$sourceName}\" thành công.",
        ], 200);
    }

    /**
     * POST/DELETE /api/admin/sources/bulk-delete
     * Xóa nhiều nguồn báo cùng lúc.
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ], [
            'ids.required' => 'Vui lòng chọn ít nhất một nguồn báo để xóa.',
        ]);

        $deletedCount = Source::whereIn('id', $validated['ids'])->delete();

        return response()->json([
            'success' => true,
            'message' => "Đã xóa thành công {$deletedCount} nguồn báo được chọn.",
            'deleted' => $deletedCount,
        ], 200);
    }

    /**
     * POST /api/admin/sources/{id}/crawl
     * Crawl RSS từ nguồn tin tức được chỉ định.
     */
    public function crawlRss(Request $request, $id)
    {
        $source = Source::findOrFail($id);

        if (!$source->rss_url) {
            return response()->json([
                'success' => false,
                'message' => "Nguồn '{$source->name}' chưa có RSS URL.",
            ], 422);
        }

        // Fetch RSS feed
        $context = stream_context_create([
            'http' => [
                'timeout'     => 15,
                'user_agent'  => 'ReadVn RSS Crawler/1.0',
                'header'      => "Accept: application/rss+xml, application/xml, text/xml\r\n",
            ],
        ]);

        $xmlContent = @file_get_contents($source->rss_url, false, $context);

        if ($xmlContent === false) {
            return response()->json([
                'success' => false,
                'message' => "Không thể kết nối đến RSS của '{$source->name}'. Vui lòng thử lại sau.",
            ], 503);
        }

        // Bỏ qua namespace warnings của SimpleXML
        libxml_use_internal_errors(true);
        $feed = simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOCDATA);
        libxml_clear_errors();

        if ($feed === false || !isset($feed->channel->item)) {
            return response()->json([
                'success' => false,
                'message' => "Không thể parse RSS feed của '{$source->name}'.",
            ], 422);
        }

        $saved   = 0;
        $skipped = 0;

        foreach ($feed->channel->item as $item) {
            // Lấy link bài báo
            $link = trim((string) $item->link);

            // Một số RSS dùng <link> với CDATA hoặc guid
            if (empty($link) && isset($item->guid)) {
                $link = trim((string) $item->guid);
            }

            if (empty($link) || !filter_var($link, FILTER_VALIDATE_URL)) {
                $skipped++;
                continue;
            }

            // Kiểm tra trùng url_hash
            $urlHash = hash('sha256', $link);
            if (Article::where('url_hash', $urlHash)->exists()) {
                $skipped++;
                continue;
            }

            // Lấy tiêu đề
            $title = trim((string) $item->title);
            if (empty($title)) {
                $skipped++;
                continue;
            }

            // ── Lấy mô tả và thumbnail từ <description> CDATA ────────────
            $rawDescription = (string) ($item->description ?? '');
            $description    = '';
            $thumbnailFromDesc = null;

            if (!empty($rawDescription)) {
                if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $rawDescription, $imgMatch)) {
                    $thumbnailFromDesc = $imgMatch[1];
                }
                $description = strip_tags($rawDescription);
                $description = trim(preg_replace('/\s+/', ' ', $description));
            }

            // Lấy ngày xuất bản
            $publishedAt = null;
            if (!empty((string) $item->pubDate)) {
                try {
                    $publishedAt = Carbon::parse((string) $item->pubDate);
                } catch (\Exception $e) {
                    $publishedAt = now();
                }
            }

            // Lấy tất cả namespace của feed và item
            $namespaces = array_merge($feed->getDocNamespaces(true) ?: [], $item->getNamespaces(true) ?: []);

            // ── Lấy tác giả từ Dublin Core namespace (dc:creator) ──────────
            $author = null;
            if (isset($namespaces['dc'])) {
                $dc        = $item->children($namespaces['dc']);
                $dcCreator = trim((string) ($dc->creator ?? ''));
                if (!empty($dcCreator)) {
                    $author = $dcCreator;
                }
            }
            if (empty($author) && !empty((string) $item->author)) {
                $rawAuthor = trim((string) $item->author);
                if (preg_match('/\((.+?)\)/', $rawAuthor, $m)) {
                    $author = trim($m[1]);
                } elseif (!str_contains($rawAuthor, '@')) {
                    $author = $rawAuthor;
                }
            }
            if (!empty($author)) {
                $author = trim(preg_replace('/[\x{2B50}\x{2605}\x{2606}]/u', '', $author));
            }

            // ── Lấy nội dung đầy đủ từ content:encoded nếu có ─────────────
            $fullContent = null;
            if (isset($namespaces['content'])) {
                $contentNs = $item->children($namespaces['content']);
                $encoded   = (string) ($contentNs->encoded ?? '');
                if (!empty(trim(strip_tags($encoded)))) {
                    $fullContent = trim($encoded);
                }
            }

            // ── Lấy thumbnail từ media:content hoặc enclosure ─────────────
            $thumbnail = null;
            if (isset($namespaces['media'])) {
                $media = $item->children($namespaces['media']);
                if (isset($media->content)) {
                    $attrs     = $media->content->attributes();
                    $thumbnail = (string) ($attrs['url'] ?? '');
                }
                if (empty($thumbnail) && isset($media->thumbnail)) {
                    $attrs     = $media->thumbnail->attributes();
                    $thumbnail = (string) ($attrs['url'] ?? '');
                }
            }
            if (empty($thumbnail) && isset($item->enclosure)) {
                $encAttrs  = $item->enclosure->attributes();
                $thumbnail = (string) ($encAttrs['url'] ?? '');
            }
            if (empty($thumbnail) && !empty($thumbnailFromDesc)) {
                $thumbnail = $thumbnailFromDesc;
            }

            // ── Nếu RSS không có fullContent hoặc author, scrape từ web gốc ────
            if (empty($fullContent)) {
                try {
                    $scraped = ScraperService::scrapeDetailFromUrl($link, $author);
                    if ($scraped) {
                        $fullContent = $scraped;
                    }
                } catch (\Exception $e) {
                    // Bỏ qua lỗi kết nối riêng lẻ
                }
            }

            // Auto slug
            $slug = Str::slug($title);
            if (strlen($slug) > 580) {
                $slug = substr($slug, 0, 580);
            }
            if (Article::where('slug', $slug)->exists()) {
                $slug = $slug . '-' . time() . '-' . rand(100, 999);
            }

            // Tính word_count
            $textForWordCount = $fullContent ? strip_tags($fullContent) : $description;
            $wordCount = $textForWordCount ? count(preg_split('/\s+/u', trim($textForWordCount), -1, PREG_SPLIT_NO_EMPTY)) : 0;

            Article::create([
                'source_id'    => $source->id,
                'category_id'  => $source->category_id,
                'title'        => $title,
                'slug'         => $slug,
                'author'       => $author ?: null,
                'summary'      => $description ?: null,
                'content'      => $fullContent ?: null,
                'thumbnail'    => $thumbnail ?: null,
                'original_url' => $link,
                'url_hash'     => $urlHash,
                'word_count'   => $wordCount,
                'published_at' => $publishedAt,
            ]);

            $saved++;
        }

        // Cập nhật thời gian crawl cuối
        $source->update(['last_crawled_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "Crawl RSS '{$source->name}' hoàn tất: đã lưu {$saved} bài, bỏ qua {$skipped} bài trùng/lỗi.",
            'saved'   => $saved,
            'skipped' => $skipped,
            'source'  => $source->name,
        ]);
    }

    /**
     * Crawl bổ sung nội dung chi tiết và tác giả từ URL bài viết gốc.
     */
    protected function scrapeDetailFromUrl(string $url, ?string &$existingAuthor = null): ?string
    {
        return ScraperService::scrapeDetailFromUrl($url, $existingAuthor);
    }
}
