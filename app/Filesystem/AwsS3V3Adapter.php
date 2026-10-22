<?php

namespace App\Filesystem;

use Illuminate\Filesystem\AwsS3V3Adapter as BaseAwsS3V3Adapter;

/**
 * The s3 disk of Laravel, with one difference: url() accepts a null path.
 *
 * Folders in the file manager are files rows with path NULL, and File::$appends
 * includes "url" (Storage::url($this->path)). Laravel 8 (Flysystem 1) returned
 * the disk's base URL for it (AWS_URL/); Laravel 9's adapter throws a TypeError
 * (PathPrefixer::prefixPath(string)), so /api/files returned 500 on S3.
 * Registered in AppServiceProvider. Check against Illuminate\Filesystem\AwsS3V3Adapter
 * in every Laravel upgrade (UPGRADE_PLAN.md).
 */
class AwsS3V3Adapter extends BaseAwsS3V3Adapter
{
    public function url($path)
    {
        return parent::url($path ?? '');
    }
}
