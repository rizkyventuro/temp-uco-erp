<?php

namespace App\Services;

use Exception;
use Aws\S3\Exception\S3Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class AmazonServerService
{
    // ─────────────────────────────────────────
    //  UPLOAD
    // ─────────────────────────────────────────

    public static function upload(
        string $directoryBase,
        UploadedFile $file,
        ?string $customFilename = null
    ): array {
        $directory = config('app.env') . '/' . $directoryBase;
        $filename  = ($customFilename ?? self::generateRandomString()) . '.' . $file->extension();

        try {
            $uploaded = Storage::disk('s3')->putFileAs($directory, $file, $filename, 'public');

            if ($uploaded) {
                return [
                    'status'   => true,
                    'url'      => config('aws.aws_bucket_url') . '/' . $uploaded,
                    'path'     => $uploaded,
                    'filename' => $filename,
                    'storage'  => 's3',
                ];
            }

            Log::warning('S3 upload returned false, falling back to local.', [
                'directory' => $directory,
                'filename'  => $filename,
            ]);
        } catch (S3Exception $e) {
            Log::error('S3Exception during upload, falling back to local.', [
                'message'   => $e->getAwsErrorMessage(),
                'directory' => $directory,
                'filename'  => $filename,
            ]);
        } catch (Exception $e) {
            Log::error('Unexpected error during S3 upload, falling back to local.', [
                'message'   => $e->getMessage(),
                'directory' => $directory,
                'filename'  => $filename,
            ]);
        }

        return self::uploadToLocal($directory, $file, $filename);
    }

    public static function uploadContent(
        string $directoryBase,
        string $content,
        string $extension,
        ?string $customFilename = null
    ): array {
        $directory = config('app.env') . '/' . $directoryBase;
        $filename  = ($customFilename ?? self::generateRandomString()) . '.' . $extension;

        try {
            $path = $directory . '/' . $filename;
            $uploaded = Storage::disk('s3')->put($path, $content, 'public');

            if ($uploaded) {
                return [
                    'status'   => true,
                    'url'      => config('aws.aws_bucket_url') . '/' . $path,
                    'path'     => $path,
                    'filename' => $filename,
                    'storage'  => 's3',
                ];
            }

            Log::warning('S3 uploadContent returned false, falling back to local.', [
                'directory' => $directory,
                'filename'  => $filename,
            ]);
        } catch (S3Exception $e) {
            Log::error('S3Exception during uploadContent, falling back to local.', [
                'message'   => $e->getAwsErrorMessage(),
                'directory' => $directory,
                'filename'  => $filename,
            ]);
        } catch (Exception $e) {
            Log::error('Unexpected error during S3 uploadContent, falling back to local.', [
                'message'   => $e->getMessage(),
                'directory' => $directory,
                'filename'  => $filename,
            ]);
        }

        return self::uploadContentToLocal($directory, $content, $filename);
    }

    // ─────────────────────────────────────────
    //  DELETE PERMANENT
    // ─────────────────────────────────────────

    public static function delete(string $filePath): array
    {
        try {
            if (!Storage::disk('s3')->exists($filePath)) {
                return ['status' => true, 'message' => 'File does not exist.'];
            }

            $deleted = Storage::disk('s3')->delete($filePath);

            if (!$deleted) {
                Log::error('S3 delete returned false.', ['path' => $filePath]);
            }

            return [
                'status'  => $deleted,
                'message' => $deleted ? 'File deleted successfully.' : 'Failed to delete file.',
            ];
        } catch (S3Exception $e) {
            Log::error('S3Exception during delete.', [
                'message' => $e->getAwsErrorMessage(),
                'path'    => $filePath,
            ]);

            return ['status' => false, 'error' => $e->getAwsErrorMessage()];
        } catch (Exception $e) {
            Log::error('Unexpected error during delete.', [
                'message' => $e->getMessage(),
                'path'    => $filePath,
            ]);

            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────
    //  MOVE TO TRASH
    // ─────────────────────────────────────────

    public static function moveToTrash(string $filePath): array
    {
        $trashPath = 'trash/' . $filePath;

        try {
            if (!Storage::disk('s3')->exists($filePath)) {
                return ['status' => false, 'error' => 'Source file does not exist.'];
            }

            $copied = Storage::disk('s3')->copy($filePath, $trashPath);

            if (!$copied) {
                Log::error('S3 copy to trash returned false, falling back to local trash.', [
                    'source' => $filePath,
                    'trash'  => $trashPath,
                ]);

                return self::moveToLocalTrash($filePath, $trashPath);
            }

            Storage::disk('s3')->delete($filePath);

            return [
                'status'     => true,
                'trash_path' => $trashPath,
                'storage'    => 's3',
            ];
        } catch (S3Exception $e) {
            Log::error('S3Exception during moveToTrash, falling back to local trash.', [
                'message' => $e->getAwsErrorMessage(),
                'source'  => $filePath,
                'trash'   => $trashPath,
            ]);

            return self::moveToLocalTrash($filePath, $trashPath);
        } catch (Exception $e) {
            Log::error('Unexpected error during moveToTrash.', [
                'message' => $e->getMessage(),
                'source'  => $filePath,
                'trash'   => $trashPath,
            ]);

            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────
    //  PRIVATE Services
    // ─────────────────────────────────────────

    private static function uploadToLocal(string $directory, UploadedFile $file, string $filename): array
    {
        try {
            $localPath = $file->storeAs($directory, $filename, 'public');

            return [
                'status'   => true,
                'url'      => asset('storage/' . $localPath),
                'path'     => $localPath,
                'filename' => $filename,
                'storage'  => 'local',
                'fallback' => true,
            ];
        } catch (Exception $e) {
            Log::error('Local storage fallback also failed.', [
                'message'   => $e->getMessage(),
                'directory' => $directory,
                'filename'  => $filename,
            ]);

            return ['status' => false, 'error' => 'Both S3 and local storage failed.'];
        }
    }

    private static function uploadContentToLocal(string $directory, string $content, string $filename): array
    {
        try {
            $localPath = $directory . '/' . $filename;
            Storage::disk('public')->put($localPath, $content);

            return [
                'status'   => true,
                'url'      => asset('storage/' . $localPath),
                'path'     => $localPath,
                'filename' => $filename,
                'storage'  => 'local',
                'fallback' => true,
            ];
        } catch (Exception $e) {
            Log::error('Local storage fallback uploadContent also failed.', [
                'message'   => $e->getMessage(),
                'directory' => $directory,
                'filename'  => $filename,
            ]);

            return ['status' => false, 'error' => 'Both S3 and local storage failed.'];
        }
    }

    private static function moveToLocalTrash(string $filePath, string $trashPath): array
    {
        try {
            if (!Storage::disk('public')->exists($filePath)) {
                return ['status' => false, 'error' => 'Source file not found on local storage either.'];
            }

            Storage::disk('public')->copy($filePath, $trashPath);
            Storage::disk('public')->delete($filePath);

            return [
                'status'     => true,
                'trash_path' => $trashPath,
                'storage'    => 'local',
                'fallback'   => true,
            ];
        } catch (Exception $e) {
            Log::error('Local trash fallback also failed.', [
                'message' => $e->getMessage(),
                'source'  => $filePath,
                'trash'   => $trashPath,
            ]);

            return ['status' => false, 'error' => 'Both S3 and local trash move failed.'];
        }
    }

    private static function generateRandomString(int $length = 10): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $result     = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $result . substr(date('YmdHis'), 0, 6);
    }

    public static function resolveUrl(?string $path, ?string $disk): ?string
    {
        if (!$path) {
            return null;
        }

        $disk ??= config('filesystems.default');

        return match ($disk) {
            's3'    => config('aws.aws_bucket_url') . '/' . $path,
            'image_google'    =>  $path,
            default => Storage::disk('public')->url($path),
        };
    }
}
