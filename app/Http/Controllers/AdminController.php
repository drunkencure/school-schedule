<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        $pendingInstructors = User::where('role', 'instructor')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        $approvedInstructors = User::where('role', 'instructor')
            ->where('status', 'approved')
            ->orderBy('name')
            ->get();

        $inactiveInstructors = User::where('role', 'instructor')
            ->where('status', 'inactive')
            ->orderBy('name')
            ->get();

        $rejectedInstructors = User::where('role', 'instructor')
            ->where('status', 'rejected')
            ->orderBy('created_at', 'desc')
            ->get();

        $subjects = Subject::orderBy('name')->get();

        return view('admin.dashboard', [
            'pendingInstructors' => $pendingInstructors,
            'approvedInstructors' => $approvedInstructors,
            'inactiveInstructors' => $inactiveInstructors,
            'rejectedInstructors' => $rejectedInstructors,
            'subjects' => $subjects,
        ]);
    }

    public function approve(User $user)
    {
        $this->ensureInstructor($user);
        $user->status = 'approved';
        $user->save();

        return back()->with('status', '강사 승인 완료');
    }

    public function reject(User $user)
    {
        $this->ensureInstructor($user);
        $user->status = 'rejected';
        $user->save();

        return back()->with('status', '강사 등록을 거절했습니다.');
    }

    public function deactivate(User $user)
    {
        $this->ensureInstructor($user);
        $user->status = 'inactive';
        $user->save();

        return back()->with('status', '강사를 비활성화했습니다.');
    }

    public function storeSubject(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:subjects,name'],
        ]);

        Subject::create($validated);

        return back()->with('status', '과목을 등록했습니다.');
    }

    private function ensureInstructor(User $user): void
    {
        if ($user->role !== 'instructor') {
            abort(404);
        }
    }
}
