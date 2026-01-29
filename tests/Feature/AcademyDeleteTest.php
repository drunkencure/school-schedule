<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_academy_and_cleanup_people(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $academy = Academy::create(['name' => '삭제 학원']);
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => 'approved',
        ]);
        $instructor->academies()->attach($academy->id, ['status' => 'approved']);

        $student = Student::create([
            'instructor_id' => $instructor->id,
            'name' => '수강생',
            'registered_at' => now()->toDateString(),
            'billing_cycle_count' => 1,
        ]);
        $student->academies()->attach($academy->id);

        $response = $this->actingAs($admin)
            ->withSession(['academy_id' => $academy->id])
            ->delete(route('admin.academies.destroy', $academy));

        $response->assertRedirect();
        $this->assertDatabaseMissing('academies', ['id' => $academy->id]);
        $this->assertDatabaseMissing('users', ['id' => $instructor->id]);
        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }
}
