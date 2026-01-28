<?php

namespace App\Http\Controllers;

use App\Models\Academy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AcademyController extends Controller
{
    public function select(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'academy_id' => ['required', 'integer', 'exists:academies,id'],
        ]);

        $academyId = (int) $validated['academy_id'];
        $allowed = $user->role === 'admin'
            ? Academy::whereKey($academyId)->exists()
            : $user->academies()->whereKey($academyId)->exists();

        if (! $allowed) {
            return back()->withErrors(['academy_id' => '선택할 수 없는 학원입니다.']);
        }

        $request->session()->put('academy_id', $academyId);

        return back();
    }
}
