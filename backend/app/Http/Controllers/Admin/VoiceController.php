<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VoiceController extends Controller
{
    /**
     * GET /api/admin/voices
     * Danh sách giọng nói AI (phân trang hoặc toàn bộ cho dropdown).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Voice::query();

        // Tìm kiếm theo tên, mã code, nhà cung cấp, ngôn ngữ
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('provider', 'like', "%{$search}%")
                  ->orWhere('language', 'like', "%{$search}%");
            });
        }

        // Lọc theo nhà cung cấp (provider)
        if ($request->filled('provider') && $request->input('provider') !== 'all') {
            $query->where('provider', $request->input('provider'));
        }

        // Lọc theo giới tính giọng đọc (gender)
        if ($request->filled('gender') && $request->input('gender') !== 'all') {
            $query->where('gender', $request->input('gender'));
        }

        // Lọc theo trạng thái hoạt động
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $isActive = filter_var($request->input('status'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        // Lấy tất cả (phục vụ chọn giọng đọc cho TTS)
        if ($request->boolean('all')) {
            $voices = $query->orderBy('provider', 'asc')->orderBy('name', 'asc')->get();
            return response()->json([
                'success' => true,
                'data'    => $voices,
            ], 200);
        }

        $perPage = min((int) $request->input('per_page', 10), 100);
        $page = max((int) $request->input('page', 1), 1);
        $paginated = $query->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);

        $total = Voice::count();
        $activeCount = Voice::where('is_active', true)->count();

        // Lấy danh sách các providers hiện có để đưa vào bộ lọc frontend
        $availableProviders = Voice::distinct()->pluck('provider')->filter()->values();

        return response()->json([
            'success' => true,
            'data'    => $paginated->items(),
            'meta'    => [
                'total'               => $paginated->total(),
                'per_page'            => $paginated->perPage(),
                'current_page'        => $paginated->currentPage(),
                'last_page'           => $paginated->lastPage(),
                'active_count'        => $activeCount,
                'available_providers' => $availableProviders,
            ],
        ], 200);
    }

    /**
     * POST /api/admin/voices
     * Thêm mới một giọng nói AI.
     */
    public function store(Request $request): JsonResponse
    {
        $provider = trim($request->input('provider', ''));
        $code = trim($request->input('code', ''));

        $validated = $request->validate([
            'provider'  => 'required|string|max:50',
            'code'      => [
                'required',
                'string',
                'max:100',
                Rule::unique('voices')->where(function ($query) use ($provider, $code) {
                    return $query->where('provider', $provider)->where('code', $code);
                }),
            ],
            'name'      => 'required|string|max:100',
            'language'  => 'nullable|string|max:10',
            'gender'    => 'nullable|in:male,female,neutral',
            'is_active' => 'nullable|boolean',
        ], [
            'provider.required' => 'Nhà cung cấp giọng đọc không được để trống.',
            'provider.max'      => 'Nhà cung cấp tối đa 50 ký tự.',
            'code.required'     => 'Mã định danh giọng nói (code) không được để trống.',
            'code.unique'       => "Mã giọng '{$code}' của nhà cung cấp '{$provider}' đã tồn tại.",
            'name.required'     => 'Tên hiển thị giọng nói không được để trống.',
            'name.max'          => 'Tên giọng nói tối đa 100 ký tự.',
            'gender.in'         => 'Giới tính giọng nói phải là male, female hoặc neutral.',
        ]);

        $voice = Voice::create([
            'provider'  => $validated['provider'],
            'code'      => $validated['code'],
            'name'      => $validated['name'],
            'language'  => $validated['language'] ?: 'vi-VN',
            'gender'    => $validated['gender'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Đã thêm giọng nói \"{$voice->name}\" thành công!",
            'data'    => $voice,
        ], 201);
    }

    /**
     * GET /api/admin/voices/{id}
     * Chi tiết một giọng nói AI.
     */
    public function show(int $id): JsonResponse
    {
        $voice = Voice::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $voice,
        ], 200);
    }

    /**
     * PUT /api/admin/voices/{id}
     * Cập nhật thông tin giọng nói AI.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $voice = Voice::findOrFail($id);

        $provider = trim($request->input('provider', $voice->provider));
        $code = trim($request->input('code', $voice->code));

        $validated = $request->validate([
            'provider'  => 'required|string|max:50',
            'code'      => [
                'required',
                'string',
                'max:100',
                Rule::unique('voices')->where(function ($query) use ($provider, $code) {
                    return $query->where('provider', $provider)->where('code', $code);
                })->ignore($voice->id),
            ],
            'name'      => 'required|string|max:100',
            'language'  => 'nullable|string|max:10',
            'gender'    => 'nullable|in:male,female,neutral',
            'is_active' => 'nullable|boolean',
        ], [
            'provider.required' => 'Nhà cung cấp giọng đọc không được để trống.',
            'provider.max'      => 'Nhà cung cấp tối đa 50 ký tự.',
            'code.required'     => 'Mã định danh giọng nói (code) không được để trống.',
            'code.unique'       => "Mã giọng '{$code}' của nhà cung cấp '{$provider}' đã tồn tại.",
            'name.required'     => 'Tên hiển thị giọng nói không được để trống.',
            'name.max'          => 'Tên giọng nói tối đa 100 ký tự.',
            'gender.in'         => 'Giới tính giọng nói phải là male, female hoặc neutral.',
        ]);

        $voice->update([
            'provider'  => $validated['provider'],
            'code'      => $validated['code'],
            'name'      => $validated['name'],
            'language'  => $validated['language'] ?: 'vi-VN',
            'gender'    => $validated['gender'] ?? null,
            'is_active' => $validated['is_active'] ?? $voice->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Đã cập nhật giọng nói \"{$voice->name}\" thành công!",
            'data'    => $voice,
        ], 200);
    }

    /**
     * PATCH /api/admin/voices/{id}/toggle-status
     * Bật / tắt trạng thái kích hoạt của giọng nói.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $voice = Voice::findOrFail($id);
        $voice->is_active = !$voice->is_active;
        $voice->save();

        $statusText = $voice->is_active ? 'kích hoạt sử dụng' : 'vô hiệu hóa';

        return response()->json([
            'success' => true,
            'message' => "Đã {$statusText} giọng nói \"{$voice->name}\".",
            'data'    => [
                'id'        => $voice->id,
                'is_active' => $voice->is_active,
            ],
        ], 200);
    }

    /**
     * DELETE /api/admin/voices/{id}
     * Xóa giọng nói.
     */
    public function destroy(int $id): JsonResponse
    {
        $voice = Voice::findOrFail($id);
        $voiceName = $voice->name;
        $voice->delete();

        return response()->json([
            'success' => true,
            'message' => "Đã xóa giọng nói \"{$voiceName}\" thành công.",
        ], 200);
    }

    /**
     * POST/DELETE /api/admin/voices/bulk-delete
     * Xóa nhiều giọng nói cùng lúc.
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ], [
            'ids.required' => 'Vui lòng chọn ít nhất một giọng nói để xóa.',
        ]);

        $deletedCount = Voice::whereIn('id', $validated['ids'])->delete();

        return response()->json([
            'success' => true,
            'message' => "Đã xóa thành công {$deletedCount} giọng nói được chọn.",
            'deleted' => $deletedCount,
        ], 200);
    }
}

