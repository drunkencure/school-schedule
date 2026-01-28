<?php

namespace App\Http\Middleware;

use App\Models\Academy;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentAcademy
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $academies = $user->role === 'admin'
            ? Academy::orderBy('name')->get()
            : $user->academies()
                ->wherePivot('status', 'approved')
                ->orderBy('name')
                ->get();

        if ($user->role === 'instructor' && $academies->isEmpty()) {
            abort(403);
        }

        $selectedId = (int) $request->session()->get('academy_id');
        $currentAcademy = $selectedId
            ? $academies->firstWhere('id', $selectedId)
            : null;

        if (! $currentAcademy && $academies->isNotEmpty()) {
            $currentAcademy = $academies->first();
            $request->session()->put('academy_id', $currentAcademy->id);
        }

        view()->share('availableAcademies', $academies);
        view()->share('currentAcademy', $currentAcademy);
        app()->instance('currentAcademy', $currentAcademy);
        app()->instance('currentAcademyId', $currentAcademy?->id);

        return $next($request);
    }
}
