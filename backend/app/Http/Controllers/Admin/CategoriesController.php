<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoriesController extends Controller
{
    /**
     * Lấy danh sách danh mục (kèm đếm bài viết và danh mục cha).
     * GET /api/admin/categories
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::query()
            ->with('parent:id,name,slug')
            ->withCount('articles');

        // Tìm kiếm theo tên hoặc slug
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Lọc theo trạng thái hoạt động
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $isActive = filter_var($request->input('status'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        $perPage = min((int) $request->input('per_page', 10), 100);
        $paginated = $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc')->paginate($perPage);

        // Thống kê nhanh
        $total = Category::count();
        $activeCount = Category::where('is_active', true)->count();

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
     * Thêm mới một danh mục tin tức.
     * POST /api/admin/categories
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:120|unique:categories,slug',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'Tên danh mục không được để trống.',
            'name.max' => 'Tên danh mục tối đa 100 ký tự.',
            'slug.unique' => 'Đường dẫn (slug) danh mục này đã tồn tại.',
            'parent_id.exists' => 'Danh mục cha không hợp lệ.',
        ]);

        // Tự động tạo slug nếu không nhập
        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);

        // Đảm bảo slug là duy nhất nếu tự sinh
        $originalSlug = $slug;
        $counter = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        // Tự tính sort_order nếu không truyền
        $sortOrder = $validated['sort_order'] ?? (Category::max('sort_order') + 1);

        $category = Category::create([
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'sort_order' => $sortOrder,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $category->load('parent:id,name,slug');
        $category->loadCount('articles');

        return response()->json([
            'success' => true,
            'message' => "Đã tạo danh mục \"{$category->name}\" thành công!",
            'data' => $category,
        ], 201);
    }

    /**
     * Xem thông tin chi tiết một danh mục.
     * GET /api/admin/categories/{id}
     */
    public function show(int $id): JsonResponse
    {
        $category = Category::with('parent:id,name,slug')->withCount('articles')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $category,
        ], 200);
    }

    /**
     * Cập nhật danh mục tin tức.
     * PUT /api/admin/categories/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => ['nullable', 'string', 'max:120', Rule::unique('categories', 'slug')->ignore($category->id)],
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                // Không cho phép danh mục cha trỏ vào chính nó
                function ($attribute, $value, $fail) use ($id) {
                    if ((int)$value === $id) {
                        $fail('Danh mục cha không thể là chính danh mục này.');
                    }
                },
            ],
            'description' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'Tên danh mục không được để trống.',
            'name.max' => 'Tên danh mục tối đa 100 ký tự.',
            'slug.unique' => 'Đường dẫn (slug) danh mục này đã tồn tại.',
            'parent_id.exists' => 'Danh mục cha không hợp lệ.',
        ]);

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);

        $category->update([
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? $category->sort_order,
            'is_active' => $validated['is_active'] ?? $category->is_active,
        ]);

        $category->load('parent:id,name,slug');
        $category->loadCount('articles');

        return response()->json([
            'success' => true,
            'message' => "Đã cập nhật danh mục \"{$category->name}\" thành công!",
            'data' => $category,
        ], 200);
    }

    /**
     * Bật/tắt trạng thái hiển thị của danh mục.
     * PATCH /api/admin/categories/{id}/toggle-status
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $category->is_active = !$category->is_active;
        $category->save();

        $statusText = $category->is_active ? 'kích hoạt hiển thị' : 'ẩn khỏi thanh điều hướng';

        return response()->json([
            'success' => true,
            'message' => "Đã {$statusText} danh mục \"{$category->name}\".",
            'data' => [
                'id' => $category->id,
                'is_active' => $category->is_active,
            ],
        ], 200);
    }

    /**
     * Xóa danh mục.
     * DELETE /api/admin/categories/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $categoryName = $category->name;

        // Cập nhật các danh mục con thành danh mục gốc (parent_id = null)
        Category::where('parent_id', $category->id)->update(['parent_id' => null]);

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => "Đã xóa danh mục \"{$categoryName}\" thành công.",
        ], 200);
    }
}

