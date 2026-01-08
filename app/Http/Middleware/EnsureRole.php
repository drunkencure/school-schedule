<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if ($user->role !== $role) {
            abort(403);
        }

        if ($role === 'instructor' && $user->status !== 'approved') {
            Auth::logout();

            return redirect()
                ->route('login')
                ->withErrors(['login_id' => '승인된 강사만 로그인할 수 있습니다.']);
        }

        return $next($request);
    }
}
