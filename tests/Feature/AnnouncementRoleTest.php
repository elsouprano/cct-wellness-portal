<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AnnouncementRoleTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_student_gets_403_on_create_announcement(): void
    {
        $user = \App\Models\User::factory()->create([
            'role' => 'student',
        ]);

        $response = $this->actingAs($user)->get('/manage/announcements/create');

        $response->assertStatus(403);
    }
}
