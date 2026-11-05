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

    public function test_current_scope_and_expiration_rules(): void
    {
        // 1. Unpublished (draft) -> excluded
        $draft = Announcement::factory()->create([
            'user_id' => $this->admin->id,
            'title' => 'Draft Announcement',
            'published_at' => null,
            'expires_at' => null,
        ]);

        // 2. Scheduled in future -> excluded
        $scheduled = Announcement::factory()->create([
            'user_id' => $this->admin->id,
            'title' => 'Scheduled Announcement',
            'published_at' => now()->addDays(2),
            'expires_at' => null,
        ]);

        // 3. Explicitly expired -> excluded
        $expired = Announcement::factory()->create([
            'user_id' => $this->admin->id,
            'title' => 'Expired Announcement',
            'published_at' => now()->subDays(2),
            'expires_at' => now()->subHour(),
        ]);

        // 4. Older than 14 days with null expiry -> excluded
        $olderThan14 = Announcement::factory()->create([
            'user_id' => $this->admin->id,
            'title' => 'Older Than 14 Days Announcement',
            'published_at' => now()->subDays(15),
            'expires_at' => null,
        ]);

        // 5. Null expiry within 14 days -> included
        $recentNullExpiry = Announcement::factory()->create([
            'user_id' => $this->admin->id,
            'title' => 'Recent Active Announcement',
            'published_at' => now()->subDays(3),
            'expires_at' => null,
        ]);

        // 6. Explicit unexpired (even if published 20 days ago) -> included
        $explicitActive = Announcement::factory()->create([
            'user_id' => $this->admin->id,
            'title' => 'Explicit Unexpired Announcement',
            'published_at' => now()->subDays(20),
            'expires_at' => now()->addDays(5),
        ]);

        $currentIds = Announcement::current()->pluck('id')->all();

        $this->assertNotContains($draft->id, $currentIds);
        $this->assertNotContains($scheduled->id, $currentIds);
        $this->assertNotContains($expired->id, $currentIds);
        $this->assertNotContains($olderThan14->id, $currentIds);
        $this->assertContains($recentNullExpiry->id, $currentIds);
        $this->assertContains($explicitActive->id, $currentIds);

        // Helper method checks
        $this->assertFalse($draft->isExpired());
        $this->assertFalse($scheduled->isExpired());
        $this->assertTrue($expired->isExpired());
        $this->assertTrue($olderThan14->isExpired());
        $this->assertFalse($recentNullExpiry->isExpired());
        $this->assertFalse($explicitActive->isExpired());
    }

    public function test_student_dashboard_displays_only_current_announcements_and_has_more_flag(): void
    {
        // Expired announcement
        Announcement::factory()->create([
            'user_id' => $this->admin->id,
            'title' => 'Old Expired Notice',
            'published_at' => now()->subDays(30),
            'expires_at' => now()->subDays(5),
        ]);

        // 4 current announcements
        $c1 = Announcement::factory()->create(['user_id' => $this->admin->id, 'title' => 'Current Notice 1', 'published_at' => now()->subMinutes(10)]);
        $c2 = Announcement::factory()->create(['user_id' => $this->admin->id, 'title' => 'Current Notice 2', 'published_at' => now()->subMinutes(20)]);
        $c3 = Announcement::factory()->create(['user_id' => $this->admin->id, 'title' => 'Current Notice 3', 'published_at' => now()->subMinutes(30)]);
        $c4 = Announcement::factory()->create(['user_id' => $this->admin->id, 'title' => 'Current Notice 4', 'published_at' => now()->subMinutes(40)]);

        $response = $this->actingAs($this->student)->get('/dashboard');
        $response->assertOk();
        $response->assertSee('Current Notice 1');
        $response->assertSee('Current Notice 2');
        $response->assertSee('Current Notice 3');
        $response->assertDontSee('Current Notice 4'); // Only 3 are taken
        $response->assertDontSee('Old Expired Notice');
        $this->assertTrue($response->viewData('hasMoreAnnouncements'));
        $this->assertCount(3, $response->viewData('recentAnnouncements'));

        // When all active announcements are deleted, empty state renders
        Announcement::whereIn('id', [$c1->id, $c2->id, $c3->id, $c4->id])->delete();

        $emptyResponse = $this->actingAs($this->student)->get('/dashboard');
        $emptyResponse->assertOk();
        $emptyResponse->assertSee("You're all caught up!", false);
        $emptyResponse->assertDontSee('Old Expired Notice');
        $this->assertFalse($emptyResponse->viewData('hasMoreAnnouncements'));
    }

    public function test_staff_form_saves_and_validates_expires_at(): void
    {
        $expiry = now()->addDays(7)->startOfMinute();

        // 1. Store with expires_at
        $response = $this->actingAs($this->admin)->post(route('announcements.store'), [
            'title' => 'New Expiring Announcement',
            'body' => 'This is a test announcement body.',
            'status' => 'publish_now',
            'expires_at' => $expiry->format('Y-m-d\TH:i'),
        ]);

        $response->assertRedirect(route('announcements.index'));
        $this->assertDatabaseHas('announcements', [
            'title' => 'New Expiring Announcement',
            'expires_at' => $expiry->format('Y-m-d H:i:00'),
        ]);

        $announcement = Announcement::where('title', 'New Expiring Announcement')->first();

        // 2. Update with new expires_at
        $newExpiry = now()->addDays(10)->startOfMinute();
        $updateResponse = $this->actingAs($this->admin)->put(route('announcements.update', $announcement), [
            'title' => 'New Expiring Announcement Updated',
            'body' => 'Updated announcement body.',
            'status' => 'keep',
            'expires_at' => $newExpiry->format('Y-m-d\TH:i'),
        ]);

        $updateResponse->assertRedirect(route('announcements.index'));
        $announcement->refresh();
        $this->assertEquals($newExpiry->format('Y-m-d H:i:00'), $announcement->expires_at->format('Y-m-d H:i:00'));

        // 3. Validation fails when expires_at is before published_at
        $invalidExpiry = now()->subDays(5);
        $invalidResponse = $this->actingAs($this->admin)->post(route('announcements.store'), [
            'title' => 'Invalid Expiry Announcement',
            'body' => 'Testing invalid expiry.',
            'status' => 'publish_now',
            'expires_at' => $invalidExpiry->format('Y-m-d\TH:i'),
        ]);

        $invalidResponse->assertSessionHasErrors('expires_at');
    }

    public function test_feed_still_lists_all_published_announcements_with_expired_badge(): void
    {
        $expired = Announcement::factory()->create([
            'user_id' => $this->admin->id,
            'title' => 'Expired Feed Item',
            'published_at' => now()->subDays(20),
            'expires_at' => null,
        ]);

        $active = Announcement::factory()->create([
            'user_id' => $this->admin->id,
            'title' => 'Active Feed Item',
            'published_at' => now()->subDays(2),
            'expires_at' => null,
        ]);

        $response = $this->actingAs($this->student)->get(route('announcements.index'));
        $response->assertOk();
        $response->assertSee('Expired Feed Item');
        $response->assertSee('Active Feed Item');
        $response->assertSee('Expired');
    }
}
