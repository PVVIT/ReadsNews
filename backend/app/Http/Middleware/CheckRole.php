<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        // 1. Chưa đăng nhập -> 401 Unauthorized
        if (! $user) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa đăng nhập hoặc phiên làm việc đã hết hạn.',
                ], 401);
            }

            return redirect()->guest(route('login'));
        }

        // 2. Tài khoản bị vô hiệu hóa -> 403 Forbidden
        if (! $user->is_active) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tài khoản của bạn hiện đang bị khóa.',
                ], 403);
            }

            abort(403, 'Tài khoản của bạn hiện đang bị khóa.');
        }

        // 3. Kiểm tra phân quyền (nếu có truyền roles)
        if (! empty($roles)) {
            // Cho phép truyền mảng hoặc chuỗi phân tách bằng dấu phẩy
            $allowedRoles = [];
            foreach ($roles as $role) {
                $parts = explode(',', $role);
                foreach ($parts as $p) {
                    $allowedRoles[] = trim($p);
                }
            }

            if (! in_array($user->role, $allowedRoles, true)) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Bạn không có quyền truy cập chức năng này (yêu cầu quyền: ' . implode(', ', $allowedRoles) . ').',
                    ], 403);
                }

                abort(403, 'Bạn không có quyền truy cập chức năng này.');
            }
        }

        return $next($request);
    }
}

