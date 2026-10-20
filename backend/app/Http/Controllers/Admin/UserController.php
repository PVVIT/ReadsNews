<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Lấy danh sách người dùng kèm bộ lọc và thống kê tổng quan.
     * GET /api/admin/users
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->select([
            'id',
            'name',
            'email',
            'role',
            'is_active',
            'avatar',
            'created_at',
            'updated_at',
        ]);

        // Tìm kiếm theo tên hoặc email
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Lọc theo vai trò
        if ($request->filled('role') && $request->input('role') !== 'all') {
            $query->where('role', $request->input('role'));
        }

        // Lọc theo trạng thái hoạt động
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $isActive = filter_var($request->input('status'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        $perPage = min((int) $request->input('per_page', 10), 100);
        $page = max((int) $request->input('page', 1), 1);
        $paginated = $query->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);

        // Thống kê nhanh toàn bộ hệ thống
        $totalUsers = User::count();
        $activeCount = User::where('is_active', true)->count();
        $inactiveCount = $totalUsers - $activeCount;

        return response()->json([
            'success' => true,
            'data' => $paginated->items(),
            'meta' => [
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'active_count'   => $activeCount,
                'inactive_count' => $inactiveCount,
            ],
        ], 200);
    }

    /**
     * Thêm mới một tài khoản người dùng từ trang quản trị.
     * POST /api/admin/users
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:191|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => ['required', Rule::in([User::ROLE_USER, User::ROLE_EDITOR, User::ROLE_ADMIN])],
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'Họ và tên không được để trống.',
            'email.required' => 'Email không được để trống.',
            'email.unique' => 'Email này đã được sử dụng.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'role.in' => 'Vai trò chỉ được là user, editor hoặc admin.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã tạo tài khoản người dùng thành công!',
            'data' => $user,
        ], 201);
    }

    /**
     * Khóa hoặc Mở khóa trạng thái hoạt động của tài khoản.
     * PATCH /api/admin/users/{id}/toggle-status
     */
    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        $currentUser = $request->user();

        // Không cho phép tự khóa tài khoản của chính mình
        if ($currentUser && $currentUser->id === $id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thể tự khóa tài khoản của chính mình!',
            ], 400);
        }

        $user = User::findOrFail($id);
        $user->is_active = ! $user->is_active;
        $user->save();

        // Nếu bị khóa tài khoản, thu hồi toàn bộ token đăng nhập hiện tại
        if (! $user->is_active) {
            $user->tokens()->delete();
        }

        $statusText = $user->is_active ? 'mở khóa hoạt động' : 'khóa tài khoản';

        return response()->json([
            'success' => true,
            'message' => "Đã {$statusText} thành công cho người dùng {$user->name}.",
            'data' => [
                'id' => $user->id,
                'is_active' => $user->is_active,
            ],
        ], 200);
    }

    /**
     * Cập nhật vai trò (phân quyền) cho người dùng.
     * PATCH /api/admin/users/{id}/role
     */
    public function updateRole(Request $request, int $id): JsonResponse
    {
        $currentUser = $request->user();

        // Không cho phép tự thay đổi vai trò của chính mình
        if ($currentUser && $currentUser->id === $id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thể tự thay đổi vai trò của chính mình!',
            ], 400);
        }

        $validated = $request->validate([
            'role' => ['required', Rule::in([User::ROLE_USER, User::ROLE_EDITOR, User::ROLE_ADMIN])],
        ], [
            'role.required' => 'Vui lòng chọn vai trò.',
            'role.in' => 'Vai trò không hợp lệ (user, editor, admin).',
        ]);

        $user = User::findOrFail($id);
        $user->role = $validated['role'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => "Đã cập nhật quyền thành công cho người dùng {$user->name}.",
            'data' => [
                'id' => $user->id,
                'role' => $user->role,
            ],
        ], 200);
    }

    /**
     * Xóa tài khoản người dùng (Soft Delete).
     * DELETE /api/admin/users/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $currentUser = $request->user();

        // Không cho phép tự xóa tài khoản của chính mình
        if ($currentUser && $currentUser->id === $id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thể tự xóa tài khoản của chính mình!',
            ], 400);
        }

        $user = User::findOrFail($id);
        $userName = $user->name;

        // Xóa các token đăng nhập
        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => "Đã xóa tài khoản {$userName} thành công.",
        ], 200);
    }

    /**
     * POST/DELETE /api/admin/users/bulk-delete
     * Xóa nhiều người dùng cùng lúc.
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $currentUser = $request->user();
        $ids = $validated['ids'];

        // Không cho phép tự xóa tài khoản của chính mình
        if ($currentUser) {
            $ids = array_values(array_filter($ids, fn($id) => (int)$id !== (int)$currentUser->id));
        }

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thể tự xóa tài khoản đang đăng nhập của chính mình!',
            ], 400);
        }

        $users = User::whereIn('id', $ids)->get();
        $deletedCount = 0;

        foreach ($users as $user) {
            $user->tokens()->delete();
            $user->delete();
            $deletedCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "Đã xóa thành công {$deletedCount} người dùng được chọn.",
            'deleted' => $deletedCount,
        ], 200);
    }
}

