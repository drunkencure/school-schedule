<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_can_register_with_selected_academy(): void
    {
        $academyA = Academy::create(['name' => '학원 A']);
        $academyB = Academy::create(['name' => '학원 B']);
        $subjectA = Subject::create([
            'academy_id' => $academyA->id,
            'name' => '과목 A',
        ]);
        $subjectB = Subject::create([
            'academy_id' => $academyB->id,
            'name' => '과목 B',
        ]);

        $response = $this->post('/register', [
            'login_id' => 'newinstructor',
            'email' => 'newinstructor@example.com',
            'name' => '신규 강사',
            'academy_ids' => [$academyA->id, $academyB->id],
            'subjects' => [$subjectA->id, $subjectB->id],
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('login'));

        $user = User::where('login_id', 'newinstructor')->first();
        $this->assertNotNull($user);
        $this->assertSame('instructor', $user->role);
        $this->assertSame('pending', $user->status);

        $this->assertDatabaseHas('academy_user', [
            'academy_id' => $academyA->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('academy_user', [
            'academy_id' => $academyB->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('instructor_subject', [
            'user_id' => $user->id,
            'subject_id' => $subjectA->id,
        ]);
        $this->assertDatabaseHas('instructor_subject', [
            'user_id' => $user->id,
            'subject_id' => $subjectB->id,
        ]);
    }
}
