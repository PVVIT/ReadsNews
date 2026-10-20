<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Source;
use App\Services\ScraperService;
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

            // ── Lấy mô tả và thumbnail từ <description> CDATA ────────────
            // VnExpress RSS: description chứa CDATA với <a><img src="..."></a> + text
            $rawDescription = (string) ($item->description ?? '');
            $description    = '';
            $thumbnailFromDesc = null;

            if (!empty($rawDescription)) {
                // Thử trích xuất URL ảnh từ thẻ <img> trong CDATA
                if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $rawDescription, $imgMatch)) {
                    $thumbnailFromDesc = $imgMatch[1];
                }
                // Lấy text thuần (bỏ HTML tags)
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
            // VnExpress, Tuổi Trẻ và nhiều báo dùng <dc:creator>
            $author = null;
            if (isset($namespaces['dc'])) {
                $dc        = $item->children($namespaces['dc']);
                $dcCreator = trim((string) ($dc->creator ?? ''));
                if (!empty($dcCreator)) {
                    $author = $dcCreator;
                }
            }
            // Fallback: thử lấy từ <author> chuẩn RSS (Tuổi Trẻ dùng thẻ này)
            if (empty($author) && !empty((string) $item->author)) {
                $rawAuthor = trim((string) $item->author);
                // RSS <author> thường dạng "email (Tên)" — lấy phần tên trong ngoặc
                if (preg_match('/\((.+?)\)/', $rawAuthor, $m)) {
                    $author = trim($m[1]);
                } elseif (!str_contains($rawAuthor, '@')) {
                    // Nếu không phải email thuần thì dùng luôn
                    $author = $rawAuthor;
                }
            }
            if (!empty($author)) {
                // Xoá ký tự ngôi sao, emoji nếu có (Tuổi Trẻ hay có ⭐)
                $author = trim(preg_replace('/[\x{2B50}\x{2605}\x{2606}]/u', '', $author));
            }

            // ── Lấy nội dung đầy đủ từ content:encoded nếu có ─────────────
            // Một số nguồn RSS cung cấp HTML đầy đủ trong <content:encoded>
            $fullContent = null;
            if (isset($namespaces['content'])) {
                $contentNs = $item->children($namespaces['content']);
                $encoded   = (string) ($contentNs->encoded ?? '');
                if (!empty(trim(strip_tags($encoded)))) {
                    $fullContent = trim($encoded); // Giữ nguyên HTML
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
                // Fallback: media:thumbnail
                if (empty($thumbnail) && isset($media->thumbnail)) {
                    $attrs     = $media->thumbnail->attributes();
                    $thumbnail = (string) ($attrs['url'] ?? '');
                }
            }
            if (empty($thumbnail) && isset($item->enclosure)) {
                $encAttrs  = $item->enclosure->attributes();
                $thumbnail = (string) ($encAttrs['url'] ?? '');
            }
            // Fallback: lấy từ ảnh trong description CDATA nếu vẫn trống
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
                    // Bỏ qua lỗi kết nối riêng lẻ để không ngắt luồng crawl
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

            // Tính word_count hỗ trợ tiếng Việt UTF-8
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
     * Hỗ trợ các nguồn báo phổ biến như VnExpress, Tuổi Trẻ.
     */
    protected function scrapeDetailFromUrl(string $url, ?string &$existingAuthor = null): ?string
    {
        return ScraperService::scrapeDetailFromUrl($url, $existingAuthor);
    }
}

