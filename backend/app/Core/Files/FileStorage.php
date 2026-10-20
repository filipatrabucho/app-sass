<?php

namespace App\Core\Files;

use App\Core\Files\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ficheiros do tenant no disco "local" (o FilesystemTenancyBootstrapper isola-o em storage/tenants/{id}).
 * Nunca são servidos diretamente: passam sempre por um controller com autorização.
 */
class FileStorage
{
    public const DISK = 'local';

    public function attach(Model $attachable, UploadedFile $file, ?int $userId = null): Attachment
    {
        $folder = Str::of(class_basename($attachable))->snake()->plural().'/'.$attachable->getKey();
        $path = $file->storeAs($folder, Str::uuid().'.'.$file->getClientOriginalExtension(), self::DISK);

        return $attachable->morphMany(Attachment::class, 'attachable')->create([
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $userId,
        ]);
    }

    public function delete(Attachment $attachment): void
    {
        Storage::disk(self::DISK)->delete($attachment->path);
        $attachment->delete();
    }

    public function download(Attachment $attachment): StreamedResponse
    {
        return Storage::disk(self::DISK)->download($attachment->path, $attachment->original_name);
    }

    public function storeAs(UploadedFile $file, string $folder, string $name): string
    {
        return $file->storeAs($folder, $name.'.'.strtolower($file->getClientOriginalExtension()), self::DISK);
    }

    public function exists(?string $path): bool
    {
        return $path !== null && Storage::disk(self::DISK)->exists($path);
    }

    public function remove(?string $path): void
    {
        if ($path !== null) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    public function response(string $path): StreamedResponse
    {
        return Storage::disk(self::DISK)->response($path, null, ['Cache-Control' => 'private, max-age=300']);
    }
}
