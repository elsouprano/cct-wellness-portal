<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'system_admin', 'email_verified_at' => now()]);
        $this->student = User::factory()->create(['role' => 'student', 'email_verified_at' => now()]);
        Storage::fake('public');
    }

    public function test_deleting_draft_works_but_posted_is_refused()
    {
        $draft = Announcement::factory()->create(['user_id' => $this->admin->id, 'published_at' => null]);
        $published = Announcement::factory()->create(['user_id' => $this->admin->id, 'published_at' => now()->subDay()]);
        $scheduled = Announcement::factory()->create(['user_id' => $this->admin->id, 'published_at' => now()->addDay()]);

        // Draft delete works
        $this->actingAs($this->admin)->delete(route('announcements.destroy', $draft))
            ->assertRedirect(route('announcements.index'))
            ->assertSessionHas('status', 'Announcement deleted successfully.');
        $this->assertDatabaseMissing('announcements', ['id' => $draft->id]);

        // Published refused
        $this->actingAs($this->admin)->delete(route('announcements.destroy', $published))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertDatabaseHas('announcements', ['id' => $published->id]);

        // Scheduled refused
        $this->actingAs($this->admin)->delete(route('announcements.destroy', $scheduled))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertDatabaseHas('announcements', ['id' => $scheduled->id]);
    }

    public function test_unpublish_turns_published_into_draft_and_allows_delete()
    {
        $published = Announcement::factory()->create(['user_id' => $this->admin->id, 'published_at' => now()->subDay()]);

        // Unpublish
        $this->actingAs($this->admin)->patch(route('announcements.unpublish', $published))
            ->assertRedirect()
            ->assertSessionHas('status');
        
        $published->refresh();
        $this->assertNull($published->published_at);

        // Delete now works
        $this->actingAs($this->admin)->delete(route('announcements.destroy', $published))
            ->assertSessionHas('status');
        $this->assertDatabaseMissing('announcements', ['id' => $published->id]);
    }

    public function test_editing_keeps_published_status_when_status_is_keep()
    {
        $publishDate = now()->subDays(2);
        $published = Announcement::factory()->create(['user_id' => $this->admin->id, 'published_at' => $publishDate]);

        $response = $this->actingAs($this->admin)->put(route('announcements.update', $published), [
            'title' => 'Updated Title',
            'body' => 'Updated Body',
            'status' => 'keep',
        ]);
        
        $response->assertSessionHasNoErrors()->assertRedirect();

        $published->refresh();
        $this->assertEquals('Updated Title', $published->title);
        $this->assertEquals($publishDate->toDateTimeString(), $published->published_at->toDateTimeString());
    }

    public function test_attachment_handling_on_update()
    {
        $published = Announcement::factory()->create(['user_id' => $this->admin->id, 'published_at' => now()->subDay()]);

        // Upload attachment
        $file = UploadedFile::fake()->create('document.pdf', 100);
        $this->actingAs($this->admin)->put(route('announcements.update', $published), [
            'title' => 'Title',
            'body' => 'Body',
            'status' => 'keep',
            'attachment' => $file,
        ]);

        $published->refresh();
        $this->assertNotNull($published->attachment_path);
        $oldPath = $published->attachment_path;
        Storage::disk('public')->assertExists($oldPath);

        // Replace attachment
        $file2 = UploadedFile::fake()->create('document2.pdf', 100);
        $this->actingAs($this->admin)->put(route('announcements.update', $published), [
            'title' => 'Title',
            'body' => 'Body',
            'status' => 'keep',
            'attachment' => $file2,
        ]);

        $published->refresh();
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($published->attachment_path);
        
        $oldPath2 = $published->attachment_path;

        // Remove attachment
        $this->actingAs($this->admin)->put(route('announcements.update', $published), [
            'title' => 'Title',
            'body' => 'Body',
            'status' => 'keep',
            'remove_attachment' => true,
        ]);

        $published->refresh();
        $this->assertNull($published->attachment_path);
        Storage::disk('public')->assertMissing($oldPath2);
    }

    public function test_student_gets_403()
    {
        $published = Announcement::factory()->create(['user_id' => $this->admin->id, 'published_at' => now()->subDay()]);

        $this->actingAs($this->student)->put(route('announcements.update', $published), ['title' => 'test', 'body' => 'test', 'status' => 'keep'])
            ->assertStatus(403);
            
        $this->actingAs($this->student)->delete(route('announcements.destroy', $published))
            ->assertStatus(403);
            
        $this->actingAs($this->student)->patch(route('announcements.unpublish', $published))
            ->assertStatus(403);
    }
}
