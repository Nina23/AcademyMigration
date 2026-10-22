<?php

namespace Tests\Feature\Api;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * API contract of a 422 validation response, as shown by the admin Vue app
 * (FileManager.vue, ItemList.vue, History.vue display error.response.data.message).
 *
 * Laravel 8 always answers with the fixed message "The given data was invalid.".
 * Laravel 9+ would use the first error text plus "(and N more errors)" instead,
 * so this test pins the current message before the framework upgrade.
 */
class ValidationMessageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Safety: never touch real storage (validation fails before anything is written).
        Storage::fake('public', ['url' => config('filesystems.disks.public.url'), 'visibility' => 'public']);
    }

    public function test_a_422_response_has_the_fixed_message_and_all_errors()
    {
        $admin = $this->createSuperUser();
        $empty = array_fill_keys(locales(), '');

        // Same payload as the admin file manager, with two invalid fields:
        // a disallowed file type and a non-integer folder.
        $response = $this->withHeaders($this->apiHeaders($admin))->post('/api/files', [
            'name' => UploadedFile::fake()->create('script.php', 1, 'application/x-php'),
            'folder_id' => 'not-a-number',
            'description' => $empty,
            'alt_attribute' => $empty,
        ]);

        $response->assertStatus(422);
        $this->assertSame('The given data was invalid.', $response->json('message'));
        $this->assertSame(['folder_id', 'name'], collect($response->json('errors'))->keys()->sort()->values()->all());
        Storage::disk('public')->assertMissing('files/script.php');
    }
}
