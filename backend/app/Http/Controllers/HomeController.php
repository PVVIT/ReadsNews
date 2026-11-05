<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * GET /api/home
     * Render toàn bộ dữ liệu trang chủ theo bố cục báo chí chuyên nghiệp (Thanh Niên layout):
     * - Danh mục hoạt động (Categories)
     * - Khối tiêu điểm (Hero spotlight: 1 tin chính + 3 tin phụ + 5 tin dòng sự kiện)
     * - Các khối chuyên mục (Category Sections: Thời sự, Kinh doanh, Thế giới, Công nghệ...)
     */
    public function index(Request $request): JsonResponse
    {
        // 1. Danh sách danh mục hoạt động
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'parent_id', 'name', 'slug', 'description']);

        // 2. Query bài viết đã xuất bản
        $articleSelect = [
            'id', 'source_id', 'category_id', 'title', 'slug',
            'summary', 'ai_summary', 'content', 'content_tts',
            'thumbnail', 'original_url', 'author', 'word_count',
            'view_count', 'listen_count', 'published_at', 'created_at',
        ];

        $baseQuery = Article::with(['category:id,name,slug', 'source:id,name,slug'])
            ->whereNotNull('published_at');

        // Lấy top bài mới nhất cho khối Hero (Tiêu điểm)
        $topArticles = (clone $baseQuery)
            ->select($articleSelect)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->take(9)
            ->get();

        $heroMain = $topArticles->first();
        $heroSub = $topArticles->slice(1, 3)->values();
        $heroTrending = $topArticles->slice(4, 5)->values();

        // 3. Khối tin theo từng chuyên mục (Thanh Niên layout: mỗi chuyên mục có 1 tin to + 4 tin nhỏ)
        $categorySections = [];
        $activeCategories = Category::where('is_active', true)->orderBy('sort_order')->get();

        foreach ($activeCategories as $cat) {
            $catArticles = (clone $baseQuery)
                ->select($articleSelect)
                ->where('category_id', $cat->id)
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->take(5)
                ->get();

            if ($catArticles->isNotEmpty()) {
                $categorySections[] = [
                    'category' => [
                        'id' => $cat->id,
                        'name' => $cat->name,
                        'slug' => $cat->slug,
                    ],
                    'main' => $catArticles->first(),
                    'items' => $catArticles->slice(1)->values(),
                ];
            }
        }

        // 4. Nếu còn ít chuyên mục có bài, tự động tạo thêm khối Tin đọc nhiều / Tin nổi bật
        $popularArticles = (clone $baseQuery)
            ->select($articleSelect)
            ->orderByDesc('view_count')
            ->orderByDesc('listen_count')
            ->take(6)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $categories,
                'hero' => [
                    'main' => $heroMain,
                    'sub' => $heroSub,
                    'trending' => $heroTrending,
                ],
                'category_sections' => $categorySections,
                'popular' => $popularArticles,
                'total_articles' => Article::whereNotNull('published_at')->count(),
            ],
        ]);
    }

    /**
     * GET /api/articles
     * Lấy danh sách bài viết theo danh mục, tìm kiếm, phân trang
     */
    public function articles(Request $request): JsonResponse
    {
        $query = Article::with(['category:id,name,slug', 'source:id,name,slug'])
            ->whereNotNull('published_at')
            ->select([
                'id', 'source_id', 'category_id', 'title', 'slug',
                'summary', 'ai_summary', 'content', 'content_tts',
                'thumbnail', 'original_url', 'author', 'word_count',
                'view_count', 'listen_count', 'published_at', 'created_at',
            ]);

        // Tìm kiếm
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%");
            });
        }

        // Lọc theo category_id hoặc category_slug
        if ($categoryId = $request->input('category_id')) {
            if ($categoryId !== 'all') {
                $query->where('category_id', $categoryId);
            }
        } elseif ($categorySlug = $request->input('category_slug')) {
            if ($categorySlug !== 'all') {
                $query->whereHas('category', function ($q) use ($categorySlug) {
                    $q->where('slug', $categorySlug);
                });
            }
        }

        // Sắp xếp
        $sort = $request->input('sort', 'latest');
        if ($sort === 'popular') {
            $query->orderByDesc('view_count')->orderByDesc('listen_count');
        } else {
            $query->orderByDesc('published_at')->orderByDesc('id');
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $paginated = $query->paginate($perPage);

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
     * GET /api/articles/{idOrSlug}
     * Chi tiết bài báo theo ID hoặc Slug (hỗ trợ .htm) + tăng lượt xem + tin liên quan
     */
    public function show($idOrSlug): JsonResponse
    {
        $cleanParam = urldecode($idOrSlug);
        // Bỏ đuôi .htm nếu có
        $cleanParam = preg_replace('/\.htm$/i', '', $cleanParam);

        $query = Article::with(['category:id,name,slug', 'source:id,name,slug'])
            ->whereNotNull('published_at');

        $article = null;

        // 1. Nếu là số ID thuần
        if (is_numeric($cleanParam)) {
            $article = (clone $query)->where('id', $cleanParam)->first();
        }

        // 2. Nếu khớp chính xác slug
        if (!$article) {
            $article = (clone $query)->where('slug', $cleanParam)->first();
        }

        // 3. Nếu dạng {slug}-{id} (giống URL Tuổi Trẻ: tieu-de-bai-bao-12345)
        if (!$article && preg_match('/^(.*)-(\d+)$/', $cleanParam, $matches)) {
            $potentialId = $matches[2];
            $potentialSlug = $matches[1];
            $article = (clone $query)->where('id', $potentialId)->first();
            if (!$article) {
                $article = (clone $query)->where('slug', $potentialSlug)->first();
            }
        }

        // 4. Tìm kiếm gần đúng theo slug nếu có tiền tố
        if (!$article) {
            $article = (clone $query)->where('slug', 'like', "{$cleanParam}%")->first();
        }

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy bài viết hoặc bài viết chưa được xuất bản.',
            ], 404);
        }

        // Tăng lượt xem
        $article->increment('view_count');

        // Lấy tin liên quan cùng chuyên mục
        $related = Article::with(['category:id,name,slug', 'source:id,name,slug'])
            ->whereNotNull('published_at')
            ->where('id', '!=', $article->id)
            ->where('category_id', $article->category_id)
            ->orderByDesc('published_at')
            ->take(6)
            ->get();

        // Lấy tin đọc nhiều nhất
        $popular = Article::with(['category:id,name,slug', 'source:id,name,slug'])
            ->whereNotNull('published_at')
            ->where('id', '!=', $article->id)
            ->orderByDesc('view_count')
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $article,
            'related' => $related,
            'popular' => $popular,
        ]);
    }

    /**
     * GET /api/categories
     * Danh sách danh mục công khai
     */
    public function categories(): JsonResponse
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->withCount(['articles' => function ($q) {
                $q->whereNotNull('published_at');
            }])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'parent_id', 'name', 'slug', 'description']);

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }
}

