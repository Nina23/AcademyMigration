<?php

namespace Tests\Feature\Images;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use TypiCMS\Modules\Classschedules\Models\Classschedule;
use TypiCMS\Modules\Files\Models\File;
use TypiCMS\Modules\Users\Models\User;

/**
 * Image pipeline as used by the application:
 *  - admin file manager upload: POST /api/files -> FileObserver -> FileUploader
 *    (default "public" disk, files/ folder) -> File::url via Storage::url
 *  - thumbnails for content (core presenter, e.g. News::thumb): signed Croppa
 *    URLs, generated on first request by the Croppa route (GD)
 *  - deleting a file removes the source and its crops.
 *
 * Local customisation (current behaviour): the Files module presenter returns
 * the ORIGINAL file URL for thumb_sm (no Croppa) in the admin file manager.
 */
class FileUploadTest extends TestCase
{
    /** @var User */
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake public disk with the real URL configuration.
        Storage::fake('public', ['url' => config('filesystems.disks.public.url'), 'visibility' => 'public']);

        // Singletons that captured the real disk during boot must be rebuilt on the fake one.
        foreach (['filesystem.disk', 'filesystem.default.driver', 'Bkwld\Croppa\Storage', 'Bkwld\Croppa\Handler', 'Bkwld\Croppa\Helpers'] as $abstract) {
            $this->app->forgetInstance($abstract);
        }
        Facade::clearResolvedInstance('Bkwld\Croppa\Helpers');

        // Safety: never touch real storage.
        $croppaRoot = $this->app->make('Bkwld\Croppa\Storage')->getSrcDisk()->getAdapter()->getPathPrefix();
        $this->assertStringContainsString('storage/framework/testing/disks/public', $croppaRoot);

        $this->admin = $this->createSuperUser();
    }

    /**
     * Same payload as the admin file manager (FileManager.vue, dropzoneSending()):
     * the file as "name", folder_id and empty description/alt_attribute per locale.
     */
    private function upload(UploadedFile $file)
    {
        $empty = array_fill_keys(locales(), '');
        $data = ['name' => $file, 'folder_id' => '', 'description' => $empty, 'alt_attribute' => $empty];

        return $this->withHeaders($this->apiHeaders($this->admin))->post('/api/files', $data);
    }

    private function createScheduleWithImage(?int $fileId): Classschedule
    {
        $id = Classschedule::create([
            'title' => ['en' => 'Schedule'], 'slug' => ['en' => 'schedule'],
            'status' => ['en' => '1'], 'summary' => ['en' => ''],
        ])->id;
        // Reload first (as the admin does): Historable's "updated" listener reads
        // $model->original[...] for every attribute.
        $schedule = Classschedule::find($id);
        $schedule->image_id = $fileId;   // not in $fillable
        $schedule->save();

        return $schedule->fresh();
    }

    /**
     * Request a Croppa URL like a browser would. Croppa's Handler singleton
     * captures the current request in its constructor; in production every image
     * request is a new PHP process, so reset it here to get the same behaviour.
     */
    /**
     * Expected URL of a file served directly from the public disk (Storage::url),
     * derived from config so it follows APP_URL (scheme, host, port).
     */
    private function publicDiskUrl(string $path): string
    {
        return rtrim(config('filesystems.disks.public.url'), '/').'/'.$path;
    }

    /**
     * Regex for a signed Croppa URL: the core presenter wraps Croppa's relative
     * "/storage/..." path in url(), so the base comes from the app URL.
     */
    private function croppaUrlPattern(string $path): string
    {
        return '#^'.preg_quote(url('storage/'.$path), '#').'\?token=[0-9a-f]{32}$#';
    }

    private function getCroppaUrl(string $url)
    {
        $this->app->forgetInstance('Bkwld\Croppa\Handler');
        $this->app->forgetInstance('Bkwld\Croppa\Helpers');
        Facade::clearResolvedInstance('Bkwld\Croppa\Helpers');

        return $this->get(parse_url($url, PHP_URL_PATH).'?'.parse_url($url, PHP_URL_QUERY));
    }

    public function test_uploading_an_image_stores_it_and_records_its_metadata()
    {
        $response = $this->upload(UploadedFile::fake()->image('Photo Of Hall.JPG', 800, 600))->assertOk();

        $response->assertJson(['model' => [
            'name' => 'photo-of-hall.jpg',        // slugged, lower-case extension
            'path' => 'files/photo-of-hall.jpg',
            'extension' => 'jpg',
            'type' => 'i',
            'width' => 800,
            'height' => 600,
            'mimetype' => 'image/jpeg',
            'folder_id' => null,
            'url' => $this->publicDiskUrl('files/photo-of-hall.jpg'),
            'thumb_sm' => $this->publicDiskUrl('files/photo-of-hall.jpg'),
        ]]);
        $response->assertJsonStructure(['model' => ['id', 'filesize', 'alt_attribute', 'description', 'alt_attribute_translated', 'children']]);

        Storage::disk('public')->assertExists('files/photo-of-hall.jpg');
        $this->assertDatabaseHas('files', ['path' => 'files/photo-of-hall.jpg', 'type' => 'i', 'width' => 800]);
    }

    public function test_uploading_the_same_file_name_twice_does_not_overwrite()
    {
        $this->upload(UploadedFile::fake()->image('poster.png', 100, 100))->assertOk();
        $this->upload(UploadedFile::fake()->image('poster.png', 50, 50))
            ->assertOk()
            ->assertJson(['model' => ['name' => 'poster_1.png', 'path' => 'files/poster_1.png', 'width' => 50]]);

        Storage::disk('public')->assertExists(['files/poster.png', 'files/poster_1.png']);
    }

    /**
     * Current behaviour: News (like Pages, Banners, Biographies, Categories,
     * Galleries, Advertismentboards and Files) overrides the presenter's image()
     * and returns the ORIGINAL file URL — no Croppa resizing — or '' without image.
     */
    public function test_news_images_use_the_original_file_url()
    {
        $file = $this->upload(UploadedFile::fake()->image('hall.jpg', 800, 600))->json('model');
        $news = $this->createNews(['image_id' => $file['id']]);

        $this->assertSame($this->publicDiskUrl('files/hall.jpg'), $news->present()->image(1200, 630));
        $this->assertSame($this->publicDiskUrl('files/hall.jpg'), $news->thumb);
        $this->assertSame('', $this->createNews(['slug' => ['sr' => 'bez-slike-cr', 'sr-latn' => 'bez-slike', 'en' => 'no-image']])->thumb);
    }

    /**
     * Modules without a presenter override (Announcements, Classschedules,
     * Departments, Newsletters, PageSections) use the core presenter: signed
     * Croppa URLs, generated on first request by the Croppa route.
     */
    public function test_core_presenter_thumbnails_are_signed_croppa_urls_generated_on_request()
    {
        $file = $this->upload(UploadedFile::fake()->image('hall.jpg', 800, 600))->json('model');
        $schedule = $this->createScheduleWithImage($file['id']);

        $url = $schedule->present()->image(240, 240, ['resize']);
        $this->assertMatchesRegularExpression($this->croppaUrlPattern('files/hall-240x240-resize.jpg'), $url);

        $response = $this->getCroppaUrl($url)->assertOk();
        $this->assertSame('image/jpeg', $response->headers->get('Content-Type'));

        Storage::disk('public')->assertExists('files/hall-240x240-resize.jpg');
        [$width, $height] = getimagesize(Storage::disk('public')->path('files/hall-240x240-resize.jpg'));
        $this->assertSame([240, 180], [$width, $height]);   // "resize" keeps the aspect ratio

        // Admin list thumbnail (height 54).
        $this->assertMatchesRegularExpression($this->croppaUrlPattern('files/hall-_x54.jpg'), $schedule->thumb);

        // Tampered token is rejected and nothing is generated. (Production answers
        // such requests with 500 instead of 404 — see known issues — so only
        // "not successful" is asserted here.)
        $this->assertFalse($this->getCroppaUrl('/storage/files/hall-100x100.jpg?token=invalid')->isSuccessful());
        Storage::disk('public')->assertMissing('files/hall-100x100.jpg');
    }

    public function test_core_presenter_uses_the_default_image_when_there_is_none()
    {
        $schedule = $this->createScheduleWithImage(null);

        $this->assertMatchesRegularExpression($this->croppaUrlPattern('img-not-found-_x54.png'), $schedule->thumb);
        Storage::disk('public')->assertExists('img-not-found.png');   // copied from public/img on first use
        $this->getCroppaUrl($schedule->thumb)->assertOk();
    }

    public function test_deleting_a_file_removes_the_record_the_source_and_its_thumbnails()
    {
        $file = $this->upload(UploadedFile::fake()->image('old.jpg', 300, 300))->json('model');
        $this->getCroppaUrl($this->createScheduleWithImage($file['id'])->present()->image(100, 100))->assertOk();
        Storage::disk('public')->assertExists(['files/old.jpg', 'files/old-100x100.jpg']);

        $this->withHeaders($this->apiHeaders($this->admin))->delete("/api/files/{$file['id']}")->assertOk();

        $this->assertNull(File::find($file['id']));
        Storage::disk('public')->assertMissing(['files/old.jpg', 'files/old-100x100.jpg']);
    }

    public function test_documents_are_stored_as_type_d_and_disallowed_types_are_rejected()
    {
        // Real (minimal) PDF bytes: FileUploader runs getimagesize() on every upload,
        // which errors on the zero-filled files UploadedFile::fake()->create() makes.
        $pdf = "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n";
        $this->upload(UploadedFile::fake()->createWithContent('Study Program.pdf', $pdf))
            ->assertOk()
            ->assertJson(['model' => ['name' => 'study-program.pdf', 'type' => 'd', 'width' => null]]);

        $this->upload(UploadedFile::fake()->create('script.php', 1, 'application/x-php'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
        Storage::disk('public')->assertMissing('files/script.php');
    }
}
