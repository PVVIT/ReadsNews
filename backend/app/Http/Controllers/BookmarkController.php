<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Bookmark;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    /**
     * GET /api/bookmarks
     * Lấy danh sách các bài báo đã bookmark của người dùng hiện tại (kèm phân trang, chuyên mục, nguồn)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $perPage = min((int) $request->input('per_page', 15), 50);

        $paginated = $user->bookmarkedArticles()
            ->with(['category:id,name,slug', 'source:id,name,slug'])
            ->whereNotNull('published_at')
            ->select([
                'articles.id', 'articles.source_id', 'articles.category_id', 'articles.title', 'articles.slug',
                'articles.summary', 'articles.ai_summary', 'articles.content', 'articles.content_tts',
                'articles.thumbnail', 'articles.original_url', 'articles.author', 'articles.word_count',
                'articles.view_count', 'articles.listen_count', 'articles.published_at', 'articles.created_at',
            ])
            ->orderByPivot('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * GET /api/bookmarks/ids
     * Lấy danh sách ID các bài báo đã bookmark (rất nhanh và nhẹ cho frontend kiểm tra trạng thái lưu)
     */
    public function ids(Request $request): JsonResponse
    {
        $user = $request->user();

        $ids = Bookmark::where('user_id', $user->id)
            ->pluck('article_id')
            ->map(fn ($id) => (int) $id);

        return response()->json([
            'success' => true,
            'data' => $ids,
        ]);
    }

    /**
     * POST /api/bookmarks/{articleId}
     * Bật/Tắt (Toggle) bookmark bài viết
     */
    public function toggle($articleId, Request $request): JsonResponse
    {
        $user = $request->user();

        $article = Article::find($articleId);
        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Bài viết không tồn tại trong hệ thống.',
            ], 404);
        }

        $existing = Bookmark::where('user_id', $user->id)
            ->where('article_id', $article->id)
            ->first();

        if ($existing) {
            // Đã lưu -> Xóa bookmark bằng composite query
            Bookmark::where('user_id', $user->id)
                ->where('article_id', $article->id)
                ->delete();

            return response()->json([
                'success' => true,
                'is_bookmarked' => false,
                'message' => 'Đã xóa khỏi danh sách bài viết đã lưu.',
            ]);
        }

        // Chưa lưu -> Tạo mới bookmark
        Bookmark::create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'is_bookmarked' => true,
            'message' => 'Đã lưu bài viết vào thư viện cá nhân của bạn.',
        ]);
    }

    /**
     * DELETE /api/bookmarks/{articleId}
     * Xóa bookmark bài viết
     */
    public function destroy($articleId, Request $request): JsonResponse
    {
        $user = $request->user();

        $deleted = Bookmark::where('user_id', $user->id)
            ->where('article_id', $articleId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => $deleted ? 'Đã xóa bài viết khỏi danh sách lưu.' : 'Bài viết chưa có trong danh sách lưu.',
        ]);
    }
}
