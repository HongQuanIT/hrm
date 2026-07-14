<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * F06: buộc người dùng có cờ must_change_password đổi mật khẩu trước khi dùng hệ thống.
 * Cho phép các route liên quan tới đổi mật khẩu và đăng xuất để tránh khoá vòng lặp.
 */
class EnsurePasswordChanged
{
    private const ALLOWED_ROUTES = [
        'account.edit',
        'account.password',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password && ! $request->routeIs(self::ALLOWED_ROUTES)) {
            return redirect()
                ->route('account.edit')
                ->with('error', 'Bạn cần đổi mật khẩu trước khi tiếp tục sử dụng hệ thống.');
        }

        return $next($request);
    }
}
