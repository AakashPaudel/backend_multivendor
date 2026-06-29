<?php

namespace App\Services\Support;

use App\Services\Service;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PublicMediaStorageService extends Service
{
    public function store(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, 'public');
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    public function replace(?string $existingPath, UploadedFile $file, string $directory): string
    {
        $this->delete($existingPath);

        return $this->store($file, $directory);
    }

    public function url(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }
}
