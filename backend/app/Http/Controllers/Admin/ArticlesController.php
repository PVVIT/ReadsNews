<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArticlesController extends Controller
{
    /**
     * GET /api/admin/articles
     * Danh sách bài báo với bộ lọc, tìm kiếm, phân trang.
     */
    public function index(Request $request)
    {
        $query = Article::with(['category:id,name,slug', 'source:id,name,slug'])
            ->select([
                'id', 'source_id', 'category_id', 'title', 'slug',
                'thumbnail', 'author', 'word_count', 'view_count',
                'listen_count', 'published_at', 'created_at',
            ]);

        // Tìm kiếm theo tiêu đề / tác giả
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%");
            });
        }

        // Lọc danh mục
        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        // Lọc nguồn
        if ($sourceId = $request->input('source_id')) {
            $query->where('source_id', $sourceId);
        }

        // Lọc trạng thái
        $status = $request->input('status', 'all');
        if ($status === 'published') {
            $query->whereNotNull('published_at');
        } elseif ($status === 'draft') {
            $query->whereNull('published_at');
        }

        $perPage = min((int) $request->input('per_page', 10), 100);

        $paginated = $query->orderByDesc('published_at')
                           ->orderByDesc('id')
                           ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $paginated->items(),
            'meta'    => [
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/admin/articles
     * Tạo bài báo mới.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:500',
            'slug'         => 'nullable|string|max:600|unique:articles,slug',
            'source_id'    => 'nullable|integer|exists:sources,id',
            'category_id'  => 'nullable|integer|exists:categories,id',
            'summary'      => 'nullable|string',
            'ai_summary'   => 'nullable|string',
            'content'      => 'nullable|string',
            'content_tts'  => 'nullable|string',
            'thumbnail'    => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'original_url' => 'required|url|max:700',
            'author'       => 'nullable|string|max:150',
            'word_count'   => 'nullable|integer|min:0',
            'published_at' => 'nullable|date',
        ]);

        // Kiểm tra trùng URL
        $urlHash = hash('sha256', trim($request->input('original_url')));
        if (Article::where('url_hash', $urlHash)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Bài báo với URL này đã tồn tại trong hệ thống.',
            ], 422);
        }

        // Auto slug
        $slug = $validated['slug'] ?? Str::slug($validated['title']);
        if (Article::where('slug', $slug)->exists()) {
            $slug = $slug . '-' . time();
        }

        // Upload thumbnail
        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            $thumbnailPath = '/storage/' . $path;
        }

        // Auto word_count
        $wordCount = $validated['word_count'] ?? null;
        if ($wordCount === null) {
            $text = strip_tags($validated['content_tts'] ?? $validated['content'] ?? '');
            $wordCount = $text ? str_word_count($text) : 0;
        }

        $article = Article::create([
            'source_id'    => $validated['source_id'] ?? null,
            'category_id'  => $validated['category_id'] ?? null,
            'title'        => $validated['title'],
            'slug'         => $slug,
            'summary'      => $validated['summary'] ?? null,
            'ai_summary'   => $validated['ai_summary'] ?? null,
            'content'      => $validated['content'] ?? null,
            'content_tts'  => $validated['content_tts'] ?? null,
            'thumbnail'    => $thumbnailPath,
            'original_url' => $request->input('original_url'),
            'url_hash'     => $urlHash,
            'author'       => $validated['author'] ?? null,
            'word_count'   => $wordCount,
            'published_at' => $validated['published_at'] ?? null,
        ]);

        $article->load(['category:id,name,slug', 'source:id,name,slug']);

        return response()->json([
            'success' => true,
            'message' => 'Tạo bài báo thành công.',
            'data'    => $article,
        ], 201);
    }

    /**
     * GET /api/admin/articles/{id}
     * Chi tiết một bài báo.
     */
    public function show($id)
    {
        $article = Article::with(['category', 'source'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $article,
        ]);
    }

    /**
     * POST /api/admin/articles/{id}   (dùng POST vì multipart/form-data không hỗ trợ PUT tốt)
     * Cập nhật bài báo.
     */
    public function update(Request $request, $id)
    {
        $article = Article::findOrFail($id);

        $validated = $request->validate([
            'title'        => 'required|string|max:500',
            'slug'         => 'nullable|string|max:600|unique:articles,slug,' . $id,
            'source_id'    => 'nullable|integer|exists:sources,id',
            'category_id'  => 'nullable|integer|exists:categories,id',
            'summary'      => 'nullable|string',
            'ai_summary'   => 'nullable|string',
            'content'      => 'nullable|string',
            'content_tts'  => 'nullable|string',
            'thumbnail'    => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'original_url' => 'required|url|max:700',
            'author'       => 'nullable|string|max:150',
            'word_count'   => 'nullable|integer|min:0',
            'published_at' => 'nullable|date',
        ]);

        // Kiểm tra trùng URL (nếu URL thay đổi)
        $newUrl  = trim($request->input('original_url'));
        $urlHash = hash('sha256', $newUrl);
        if ($newUrl !== $article->original_url) {
            if (Article::where('url_hash', $urlHash)->where('id', '!=', $id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bài báo với URL này đã tồn tại trong hệ thống.',
                ], 422);
            }
        }

        // Auto slug
        $slug = $validated['slug'] ?? $article->slug;
        if (!$slug) {
            $slug = Str::slug($validated['title']);
            if (Article::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = $slug . '-' . time();
            }
        }

        // Upload thumbnail mới (nếu có)
        $thumbnailPath = $article->thumbnail;
        if ($request->hasFile('thumbnail')) {
            // Xóa ảnh cũ nếu là file local
            if ($thumbnailPath && str_starts_with($thumbnailPath, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $thumbnailPath);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('thumbnail')->store('thumbnails', 'public');
            $thumbnailPath = '/storage/' . $path;
        }

        $article->update([
            'source_id'    => $validated['source_id'] ?? null,
            'category_id'  => $validated['category_id'] ?? null,
            'title'        => $validated['title'],
            'slug'         => $slug,
            'summary'      => $validated['summary'] ?? null,
            'ai_summary'   => $validated['ai_summary'] ?? null,
            'content'      => $validated['content'] ?? null,
            'content_tts'  => $validated['content_tts'] ?? null,
            'thumbnail'    => $thumbnailPath,
            'original_url' => $newUrl,
            'url_hash'     => $urlHash,
            'author'       => $validated['author'] ?? null,
            'word_count'   => $validated['word_count'] ?? $article->word_count,
            'published_at' => $validated['published_at'] ?? $article->published_at,
        ]);

        $article->load(['category:id,name,slug', 'source:id,name,slug']);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật bài báo thành công.',
            'data'    => $article,
        ]);
    }

    /**
     * PATCH /api/admin/articles/{id}/toggle-publish
     * Bật/tắt trạng thái xuất bản.
     */
    public function togglePublish($id)
    {
        $article = Article::findOrFail($id);

        if ($article->published_at !== null) {
            $article->published_at = null; // Chuyển về bản nháp
        } else {
            $article->published_at = now(); // Xuất bản ngay
        }
        $article->save();

        return response()->json([
            'success'      => true,
            'message'      => $article->published_at ? 'Đã xuất bản bài báo.' : 'Đã chuyển về bản nháp.',
            'id'           => $article->id,
            'published_at' => $article->published_at,
        ]);
    }

    /**
     * DELETE /api/admin/articles/{id}
     * Xóa bài báo.
     */
    public function destroy($id)
    {
        $article = Article::findOrFail($id);

        // Xóa thumbnail nếu là file local
        if ($article->thumbnail && str_starts_with($article->thumbnail, '/storage/')) {
            $path = str_replace('/storage/', '', $article->thumbnail);
            Storage::disk('public')->delete($path);
        }

        $article->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa bài báo thành công.',
        ]);
    }
}

