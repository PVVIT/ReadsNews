<?php

namespace App\Services;

class ScraperService
{
    /**
     * Crawl bổ sung nội dung chi tiết và tác giả từ URL bài viết gốc.
     * Hỗ trợ các nguồn báo phổ biến như VnExpress, Tuổi Trẻ.
     */
    public static function scrapeDetailFromUrl(string $url, ?string &$existingAuthor = null): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return null;
        }

        $context = stream_context_create([
            'http' => [
                'timeout'    => 5,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ],
        ]);

        $html = @file_get_contents($url, false, $context);
        if (!$html) {
            return null;
        }

        $body = null;

        // Xử lý VnExpress
        if (str_contains($host, 'vnexpress.net')) {
            if (preg_match('/<article[^>]*class=["\'][^"\']*fck_detail[^"\']*["\'][^>]*>(.*?)<\/article>/is', $html, $m)) {
                $body = $m[1];
            }
            // Tìm tác giả nếu chưa có
            if (empty($existingAuthor)) {
                if (preg_match('/<p[^>]*class=["\'][^"\']*(?:author|Normal)[^"\']*["\'][^>]*style=["\'][^"\']*text-align:\s*right[^"\']*["\'][^>]*>(.*?)<\/p>/is', $html, $mAuthor)) {
                    $existingAuthor = trim(strip_tags($mAuthor[1]));
                } elseif (preg_match('/<p[^>]*class=["\'][^"\']*author_mail[^"\']*["\'][^>]*>(.*?)<\/p>/is', $html, $mAuthor)) {
                    $existingAuthor = trim(strip_tags($mAuthor[1]));
                }
            }
        }
        // Xử lý Tuổi Trẻ
        elseif (str_contains($host, 'tuoitre.vn')) {
            if (preg_match('/<div[^>]*class=["\'][^"\']*detail-content[^"\']*["\'][^>]*>(.*?)<div[^>]*class=["\'][^"\']*(?:detail-author|author|detail-tab-tag)/is', $html, $m)) {
                $body = $m[1];
            }
        }

        if ($body) {
            // Loại bỏ các thẻ script, style, svg không mong muốn
            $body = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $body);
            $body = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $body);
            $body = preg_replace('/<svg\b[^>]*>(.*?)<\/svg>/is', '', $body);

            // Chuẩn hoá hình ảnh sang định dạng chuẩn tương thích Quill Editor
            $body = self::normalizeImages($body);

            return trim($body);
        }

        return null;
    }

    /**
     * Chuẩn hoá tất cả các khối hình ảnh và thẻ <img> trong bài viết:
     * - Mở khóa <figure> và <picture> thành các thẻ <p><img ...></p> để Quill Editor hiển thị được
     * - Chuyển data-src, data-original, data-srcset thành src thật
     * - Loại bỏ base64 placeholder 1x1 của lazy-loading
     * - Thêm referrerpolicy="no-referrer" để chống chặn hotlink từ CDN báo
     */
    public static function normalizeImages(string $html): string
    {
        // 1. Chuyển đổi thẻ <figure> (thường dùng ở VnExpress và Tuổi Trẻ) thành cấu trúc chuẩn cho Quill Editor
        $html = preg_replace_callback('/<figure\b[^>]*>(.*?)<\/figure>/is', function ($match) {
            $inner = $match[1];

            // Tìm URL ảnh thực
            $imgUrl = null;
            if (preg_match('/(?:data-src|data-original)=["\']([^"\']+)["\']/i', $inner, $m)) {
                $imgUrl = $m[1];
            } elseif (preg_match('/<img\b[^>]*?src=["\']([^"\']+)["\']/i', $inner, $m) && !str_starts_with($m[1], 'data:image')) {
                $imgUrl = $m[1];
            }

            if (!$imgUrl) {
                // Fallback: quét mọi thuộc tính chứa URL ảnh
                if (preg_match('/https?:\/\/[^\s"\']+\.(?:jpg|jpeg|png|webp|gif)(?:\?[^\s"\']*)?/i', $inner, $mUrl)) {
                    $imgUrl = $mUrl[0];
                }
            }

            if (!$imgUrl) {
                return '';
            }

            // Lấy caption chú thích ảnh nếu có
            $caption = '';
            if (preg_match('/<figcaption\b[^>]*>(.*?)<\/figcaption>/is', $inner, $mCap)) {
                $caption = trim(strip_tags($mCap[1]));
            } elseif (preg_match('/<p[^>]*class=["\'][^"\']*(?:caption|Image|PhotoCMS_Caption)[^"\']*["\'][^>]*>(.*?)<\/p>/is', $inner, $mCap)) {
                $caption = trim(strip_tags($mCap[1]));
            }

            $out = '<p><img src="' . htmlspecialchars($imgUrl, ENT_QUOTES) . '" referrerpolicy="no-referrer" style="max-width: 100%; height: auto; border-radius: 8px; display: block; margin: 12px auto;" /></p>';
            if ($caption) {
                $out .= '<p style="text-align: center; font-size: 13px; color: #64748b; margin-top: -6px; margin-bottom: 16px;"><em>' . htmlspecialchars($caption, ENT_QUOTES) . '</em></p>';
            }

            return $out;
        }, $html);

        // 2. Chuyển đổi <picture> nếu còn sót lại
        $html = preg_replace_callback('/<picture\b[^>]*>(.*?)<\/picture>/is', function ($match) {
            $inner = $match[1];
            $imgUrl = null;
            if (preg_match('/(?:data-src|data-original)=["\']([^"\']+)["\']/i', $inner, $m)) {
                $imgUrl = $m[1];
            } elseif (preg_match('/<img\b[^>]*?src=["\']([^"\']+)["\']/i', $inner, $m) && !str_starts_with($m[1], 'data:image')) {
                $imgUrl = $m[1];
            }

            if ($imgUrl) {
                return '<p><img src="' . htmlspecialchars($imgUrl, ENT_QUOTES) . '" referrerpolicy="no-referrer" style="max-width: 100%; height: auto; border-radius: 8px; display: block; margin: 12px auto;" /></p>';
            }
            return '';
        }, $html);

        // 3. Chuẩn hoá các thẻ <img> đơn lẻ còn lại (chưa nằm trong <p>)
        $html = preg_replace_callback('/(?<!<p>)<img\b([^>]*?)>(?!<\/p>)/i', function ($match) {
            $attrs = $match[1];

            $realUrl = null;
            if (preg_match('/(?:data-src|data-original)=["\']([^"\']+)["\']/i', $attrs, $m)) {
                $realUrl = $m[1];
            } elseif (preg_match('/src=["\']([^"\']+)["\']/i', $attrs, $m) && !str_starts_with($m[1], 'data:image')) {
                $realUrl = $m[1];
            }

            if ($realUrl) {
                return '<p><img src="' . htmlspecialchars($realUrl, ENT_QUOTES) . '" referrerpolicy="no-referrer" style="max-width: 100%; height: auto; border-radius: 8px; display: block; margin: 12px auto;" /></p>';
            }

            return '';
        }, $html);

        return $html;
    }
}
