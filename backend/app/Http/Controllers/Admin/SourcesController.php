<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Source;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SourcesController extends Controller
{
    /**
     * GET /api/admin/sources
     * Danh sách nguồn tin tức đang hoạt động.
     */
    public function index()
    {
        $sources = Source::withCount('articles')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $sources,
        ]);
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

            // Lấy mô tả (strip HTML tags)
            $description = strip_tags((string) ($item->description ?? ''));
            $description = trim(preg_replace('/\s+/', ' ', $description));

            // Lấy ngày xuất bản
            $publishedAt = null;
            if (!empty((string) $item->pubDate)) {
                try {
                    $publishedAt = Carbon::parse((string) $item->pubDate);
                } catch (\Exception $e) {
                    $publishedAt = now();
                }
            }

            // Lấy thumbnail từ media:content hoặc enclosure
            $thumbnail = null;
            $namespaces = $item->getNamespaces(true);
            if (isset($namespaces['media'])) {
                $media = $item->children($namespaces['media']);
                if (isset($media->content)) {
                    $attrs = $media->content->attributes();
                    $thumbnail = (string) ($attrs['url'] ?? '');
                }
            }
            if (empty($thumbnail) && isset($item->enclosure)) {
                $encAttrs = $item->enclosure->attributes();
                $thumbnail = (string) ($encAttrs['url'] ?? '');
            }

            // Auto slug
            $slug = Str::slug($title);
            if (strlen($slug) > 580) {
                $slug = substr($slug, 0, 580);
            }
            if (Article::where('slug', $slug)->exists()) {
                $slug = $slug . '-' . time() . '-' . rand(100, 999);
            }

            // Auto word_count
            $wordCount = $description ? str_word_count($description) : 0;

            Article::create([
                'source_id'    => $source->id,
                'category_id'  => $source->category_id,
                'title'        => $title,
                'slug'         => $slug,
                'summary'      => $description ?: null,
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
}

