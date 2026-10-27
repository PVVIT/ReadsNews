<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Lấy thông tin profile của user đang đăng nhập.
     * GET /api/profile
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => $this->formatUser($user),
        ]);
    }

    /**
     * Cập nhật thông tin cá nhân (tên, ngày sinh, giới tính, SĐT).
     * PUT /api/profile
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'       => 'required|string|max:100',
            'birth_date' => 'nullable|date|before:today',
            'gender'     => ['nullable', Rule::in(['male', 'female', 'other'])],
            'phone'      => 'nullable|string|max:20|regex:/^[0-9+\-\s()]+$/',
        ], [
            'name.required'      => 'Họ tên không được để trống.',
            'name.max'           => 'Họ tên không được vượt quá 100 ký tự.',
            'birth_date.date'    => 'Ngày sinh không hợp lệ.',
            'birth_date.before'  => 'Ngày sinh phải trước ngày hôm nay.',
            'gender.in'          => 'Giới tính không hợp lệ.',
            'phone.max'          => 'Số điện thoại không được vượt quá 20 ký tự.',
            'phone.regex'        => 'Số điện thoại chỉ được chứa số, dấu +, -, dấu cách.',
        ]);

        $user->fill($validated);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thông tin cá nhân thành công!',
            'data'    => $this->formatUser($user),
        ]);
    }

    /**
     * Upload ảnh đại diện.
     * POST /api/profile/avatar
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ], [
            'avatar.required' => 'Vui lòng chọn ảnh đại diện.',
            'avatar.image'    => 'File phải là ảnh.',
            'avatar.mimes'    => 'Chỉ chấp nhận ảnh JPG, PNG, GIF, WebP.',
            'avatar.max'      => 'Kích thước ảnh không được vượt quá 2MB.',
        ]);

        $user = $request->user();

        // Xóa ảnh cũ nếu có và lưu trong local public storage
        if ($user->avatar && str_starts_with($user->avatar, 'avatars/')) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Lưu ảnh mới
        $path = $request->file('avatar')->store('avatars', 'public');

        $user->avatar = $path;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật ảnh đại diện thành công!',
            'data'    => [
                'avatar'     => $user->avatar,
                'avatar_url' => $this->getAvatarUrl($user->avatar),
            ],
        ]);
    }

    /**
     * Đổi mật khẩu.
     * PUT /api/profile/password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password'      => 'required|string',
            'password'              => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required|string',
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'password.required'         => 'Vui lòng nhập mật khẩu mới.',
            'password.min'              => 'Mật khẩu mới phải có ít nhất 6 ký tự.',
            'password.confirmed'        => 'Xác nhận mật khẩu mới không trùng khớp.',
            'password_confirmation.required' => 'Vui lòng xác nhận mật khẩu mới.',
        ]);

        // Kiểm tra mật khẩu hiện tại
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu hiện tại không đúng.',
                'errors'  => ['current_password' => ['Mật khẩu hiện tại không đúng.']],
            ], 422);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Đổi mật khẩu thành công!',
        ]);
    }

    private function getAvatarUrl(?string $avatar): ?string
    {
        if (!$avatar) {
            return null;
        }

        if (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')) {
            return $avatar;
        }

        return url(Storage::url($avatar));
    }

    /**
     * Format thông tin user trả về cho client.
     */
    private function formatUser($user): array
    {
        return [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'role'       => $user->role,
            'is_active'  => $user->is_active,
            'avatar'     => $user->avatar,
            'avatar_url' => $this->getAvatarUrl($user->avatar),
            'birth_date' => $user->birth_date ? (is_string($user->birth_date) ? substr($user->birth_date, 0, 10) : $user->birth_date->format('Y-m-d')) : null,
            'gender'     => $user->gender,
            'phone'      => $user->phone,
            'created_at' => $user->created_at,
        ];
    }
}

