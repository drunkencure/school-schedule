<?php

namespace App\Http\Controllers;

use App\Models\Academy;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('login_id', $credentials['login_id'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors([
                'login_id' => '아이디 또는 비밀번호가 올바르지 않습니다.',
            ])->onlyInput('login_id');
        }

        if ($user->role === 'instructor') {
            if ($user->status !== 'approved') {
                return back()->withErrors([
                    'login_id' => '승인된 강사만 로그인할 수 있습니다.',
                ])->onlyInput('login_id');
            }

            $hasApprovedAcademy = $user->academies()
                ->wherePivot('status', 'approved')
                ->exists();
            if (! $hasApprovedAcademy) {
                return back()->withErrors([
                    'login_id' => '승인된 학원에 소속된 강사만 로그인할 수 있습니다.',
                ])->onlyInput('login_id');
            }
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('instructor.dashboard');
    }

    public function showRegister(): View
    {
        $academies = Academy::orderBy('name')->get();
        $selectedAcademyIds = old('academy_ids', $academies->first()?->id ? [$academies->first()->id] : []);
        $selectedAcademyIds = collect($selectedAcademyIds)
            ->map(function ($academyId) {
                return (int) $academyId;
            })
            ->filter()
            ->values()
            ->all();
        $allSubjects = $academies->isEmpty()
            ? collect()
            : Subject::whereIn('academy_id', $academies->pluck('id'))
                ->orderBy('name')
                ->get();
        $selectedHasSubjects = ! empty($selectedAcademyIds)
            && $allSubjects->whereIn('academy_id', $selectedAcademyIds)->isNotEmpty();

        return view('auth.register', [
            'academies' => $academies,
            'selectedAcademyIds' => $selectedAcademyIds,
            'allSubjects' => $allSubjects,
            'selectedHasSubjects' => $selectedHasSubjects,
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $academyIds = collect((array) $request->input('academy_ids', []))
            ->map(function ($academyId) {
                return (int) $academyId;
            })
            ->filter()
            ->values()
            ->all();
        $validated = $request->validate([
            'login_id' => ['required', 'string', 'max:50', 'unique:users,login_id', Rule::notIn(['admin'])],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'name' => ['required', 'string', 'max:255'],
            'academy_ids' => ['required', 'array', 'min:1'],
            'academy_ids.*' => ['integer', 'exists:academies,id'],
            'subjects' => ['required', 'array', 'min:1'],
            'subjects.*' => ['integer', Rule::exists('subjects', 'id')->where(function ($query) use ($academyIds) {
                $query->whereIn('academy_id', $academyIds);
            })],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'academy_ids.required' => '학원을 하나 이상 선택해야 합니다.',
            'academy_ids.min' => '학원을 하나 이상 선택해야 합니다.',
        ]);

        $academyIds = array_values(array_unique($validated['academy_ids']));

        $user = User::create([
            'login_id' => $validated['login_id'],
            'email' => $validated['email'],
            'name' => $validated['name'],
            'password' => Hash::make($validated['password']),
            'role' => 'instructor',
            'status' => 'pending',
        ]);

        $user->subjects()->sync($validated['subjects']);
        $academyPayload = collect($academyIds)->mapWithKeys(function ($academyId) {
            return [$academyId => ['status' => 'pending']];
        })->all();
        $user->academies()->syncWithoutDetaching($academyPayload);

        return redirect()
            ->route('login')
            ->with('status', '등록 요청이 완료되었습니다. 관리자 승인 후 로그인할 수 있습니다.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
