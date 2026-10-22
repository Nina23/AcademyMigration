<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Production stores files on S3; the rest of the suite uses the local disk.
 * Folders are files rows with type "f" and path NULL, and File::$appends
 * includes "url" (Storage::url($this->path)). On S3, Laravel 8 returns the
 * disk's base URL for a NULL path; this pins that the file manager list keeps
 * working and returns the same value.
 *
 * The s3 disk is configured with dummy values; Storage::url() needs no network.
 */
class FilesS3UrlTest extends TestCase
{
    private const S3_URL = 'https://s3-test.invalid/akademija-test';

    private function useS3Disk(): void
    {
        config([
            'filesystems.default' => 's3',
            'filesystems.disks.s3' => array_merge(config('filesystems.disks.s3'), [
                'key' => 'test-key',
                'secret' => 'test-secret',
                'region' => 'us-east-1',
                'bucket' => 'akademija-test',
                'url' => self::S3_URL,
                'endpoint' => 'https://s3-test.invalid',
            ]),
        ]);
        $this->app['filesystem']->forgetDisk('s3');
    }

    private function createFolder(string $name, ?int $parentId = null): int
    {
        return DB::table('files')->insertGetId([
            'folder_id' => $parentId,
            'type' => 'f',
            'name' => $name,
            'path' => null,
            'description' => '{}',
            'alt_attribute' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_file_manager_lists_a_folder_with_sub_folders_on_s3()
    {
        $this->useS3Disk();
        $parent = $this->createFolder('S3 parent');
        $child = $this->createFolder('S3 child', $parent);
        $this->createFolder('S3 grandchild', $child);

        $response = $this->withHeaders($this->apiHeaders($this->createSuperUser()))
            ->getJson('/api/files?folder_id='.$parent);

        $response->assertOk();
        $folder = collect($response->json('models'))->firstWhere('id', $child);
        $this->assertNotNull($folder);
        $this->assertSame(self::S3_URL.'/', $folder['url']);
        $this->assertSame(self::S3_URL.'/', $folder['children'][0]['url']);
    }
}
