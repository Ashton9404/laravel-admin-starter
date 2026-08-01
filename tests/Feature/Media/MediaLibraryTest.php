<?php

namespace Tests\Feature\Media;

use App\Models\Media;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);
        $this->manager = User::factory()->withRole(Role::MANAGER)->create();
    }

    public function test_guests_cannot_browse_the_library(): void
    {
        $this->getJson('/api/media')->assertUnauthorized();
    }

    public function test_a_user_without_the_permission_is_forbidden(): void
    {
        $this->actingAs(User::factory()->withRole(Role::USER)->create())
            ->getJson('/api/media')
            ->assertForbidden();
    }

    public function test_a_manager_can_upload_a_file(): void
    {
        $response = $this->actingAs($this->manager)
            ->postJson('/api/media', ['file' => UploadedFile::fake()->image('photo.png')])
            ->assertCreated()
            ->assertJsonPath('data.name', 'photo.png')
            ->assertJsonPath('data.is_image', true);

        $media = Media::first();
        Storage::disk('public')->assertExists($media->path);
        $this->assertSame($this->manager->id, $media->uploaded_by);
        $this->assertNotNull($response->json('data.url'));
    }

    public function test_the_stored_name_does_not_collide_with_an_earlier_upload(): void
    {
        foreach (range(1, 2) as $ignored) {
            $this->actingAs($this->manager)
                ->postJson('/api/media', ['file' => UploadedFile::fake()->image('logo.png')])
                ->assertCreated();
        }

        $paths = Media::pluck('path');

        // Both keep the display name; only the storage key differs, so the
        // second upload cannot silently replace the first.
        $this->assertCount(2, $paths->unique());
        $this->assertSame(2, Media::where('name', 'logo.png')->count());
    }

    public function test_svg_uploads_are_rejected(): void
    {
        // SVG is XML and can carry <script>; served from our own origin the
        // browser would execute it.
        $this->actingAs($this->manager)
            ->postJson('/api/media', [
                'file' => UploadedFile::fake()->create('icon.svg', 8, 'image/svg+xml'),
            ])
            ->assertUnprocessable()->assertJsonValidationErrorFor('file');
    }

    public function test_executable_uploads_are_rejected(): void
    {
        $this->actingAs($this->manager)
            ->postJson('/api/media', [
                'file' => UploadedFile::fake()->create('shell.php', 8, 'application/x-php'),
            ])
            ->assertUnprocessable()->assertJsonValidationErrorFor('file');
    }

    public function test_oversized_uploads_are_rejected(): void
    {
        $this->actingAs($this->manager)
            ->postJson('/api/media', ['file' => UploadedFile::fake()->create('big.png', 9000, 'image/png')])
            ->assertUnprocessable()->assertJsonValidationErrorFor('file');
    }

    public function test_the_library_can_be_filtered_to_images(): void
    {
        Media::factory()->create();
        Media::factory()->pdf()->create();

        $all = $this->actingAs($this->manager)->getJson('/api/media')->assertOk()->json('data');
        $images = $this->actingAs($this->manager)->getJson('/api/media?images_only=1')->assertOk()->json('data');

        $this->assertCount(2, $all);
        $this->assertCount(1, $images);
        $this->assertTrue($images[0]['is_image']);
    }

    public function test_the_library_can_be_searched_by_name(): void
    {
        Media::factory()->create(['name' => 'brochure-cover.png']);
        Media::factory()->create(['name' => 'unrelated.png']);

        $data = $this->actingAs($this->manager)->getJson('/api/media?search=brochure')->assertOk()->json('data');

        $this->assertCount(1, $data);
        $this->assertSame('brochure-cover.png', $data[0]['name']);
    }

    public function test_deleting_a_record_removes_the_file_too(): void
    {
        $this->actingAs($this->manager)
            ->postJson('/api/media', ['file' => UploadedFile::fake()->image('photo.png')])
            ->assertCreated();

        $media = Media::first();
        Storage::disk('public')->assertExists($media->path);

        $this->actingAs($this->manager)->deleteJson("/api/media/{$media->id}")->assertOk();

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        // The row and the bytes go together — no orphans left on disk.
        Storage::disk('public')->assertMissing($media->path);
    }

    public function test_an_uploader_may_delete_their_own_file_without_the_delete_permission(): void
    {
        $author = User::factory()->withRole(Role::USER)->create();
        $media = Media::factory()->create(['uploaded_by' => $author->id]);

        $this->assertFalse($author->hasPermission('media.delete'));
        $this->actingAs($author)->deleteJson("/api/media/{$media->id}")->assertOk();
    }

    public function test_a_user_cannot_delete_someone_elses_file(): void
    {
        $media = Media::factory()->create(['uploaded_by' => $this->manager->id]);

        $this->actingAs(User::factory()->withRole(Role::USER)->create())
            ->deleteJson("/api/media/{$media->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }

    public function test_deleting_the_uploader_keeps_the_file(): void
    {
        $author = User::factory()->create();
        $media = Media::factory()->create(['uploaded_by' => $author->id]);

        $author->delete();

        // The files belong to the organisation, not the individual.
        $this->assertDatabaseHas('media', ['id' => $media->id, 'uploaded_by' => null]);
    }
}
