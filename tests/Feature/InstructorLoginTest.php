<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_with_approved_academy_can_login(): void
    {
        $academy = Academy::create(['name' => '승인 학원']);
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => 'approved',
        ]);
        $instructor->academies()->attach($academy->id, ['status' => 'approved']);

        $response = $this->post('/login', [
            'login_id' => $instructor->login_id,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('instructor.dashboard'));
        $this->assertAuthenticatedAs($instructor);
    }

    public function test_instructor_without_approval_cannot_login(): void
    {
        $academy = Academy::create(['name' => '대기 학원']);
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => 'pending',
        ]);
        $instructor->academies()->attach($academy->id, ['status' => 'approved']);

        $response = $this->post('/login', [
            'login_id' => $instructor->login_id,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('login_id');
        $this->assertGuest();
    }
}
