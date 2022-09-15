<?php

namespace Plugins\LaravelLocalStorage\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Plugins\LaravelLocalStorage\Models\File as FileModel;

class BusinessService
{
    public static function make()
    {
        return new static();
    }

    public function getStorage($disk = null)
    {
        return Storage::disk($disk);
    }
    
    public function upload(array $data): ?array
    {
        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $data['file'] ?? null;
        $path = $data['path'];

        if (!($file instanceof UploadedFile)) {
            throw new \RuntimeException("请上传文件");
        }

        $mimeType = File::mimeType($file->getRealPath());
        $type = match (true) {
            str_contains($mimeType, 'image/') => FileModel::TYPE_IMAGE,
            str_contains($mimeType, 'video/') => FileModel::TYPE_VIDEO,
            default => FileModel::TYPE_UNKNOWN,
        };

        if (function_exists('tenant') && tenant()) {
            $path = sprintf('public/%s', $path);
        }

        $path = $this->getStorage()->put($path, $file);

        $result = [
            'name' => $file->getClientOriginalName(),
            'type' => $type,
            'mime' => $mimeType,
            'path' => $path,
            'url' => \URL::tenantFile(str_replace('public/', '', $path)),
        ];

        $file = central(function () use ($result) {
            return FileModel::addFile($result);
        });

        return $file->getDetail();
    }
}
