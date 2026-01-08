<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
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

        if ($user->role === 'instructor' && $user->status !== 'approved') {
            return back()->withErrors([
                'login_id' => '승인된 강사만 로그인할 수 있습니다.',
            ])->onlyInput('login_id');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('instructor.dashboard');
    }

    public function showRegister()
    {
        $subjects = Subject::orderBy('name')->get();

        return view('auth.register', [
            'subjects' => $subjects,
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'login_id' => ['required', 'string', 'max:50', 'unique:users,login_id', Rule::notIn(['admin'])],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'name' => ['required', 'string', 'max:255'],
            'subjects' => ['required', 'array', 'min:1'],
            'subjects.*' => ['integer', 'exists:subjects,id'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::create([
            'login_id' => $validated['login_id'],
            'email' => $validated['email'],
            'name' => $validated['name'],
            'password' => Hash::make($validated['password']),
            'role' => 'instructor',
            'status' => 'pending',
        ]);

        $user->subjects()->sync($validated['subjects']);

        return redirect()
            ->route('login')
            ->with('status', '등록 요청이 완료되었습니다. 관리자 승인 후 로그인할 수 있습니다.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
