<?php

namespace App\Actions;

use App\Models\Photo;
use App\Support\ImagePreview;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class StorePortfolioPhoto
{
    public function handle(UploadedFile $file, bool $asHero): Photo
    {
        $stem = Str::of($file->getClientOriginalName())
            ->beforeLast('.')
            ->replace(['-', '_'], ' ')
            ->squish()
            ->toString();

        $asHero = $asHero || strcasecmp($stem, 'hero') === 0;
        $extension = $file->extension() === 'jpeg' ? 'jpg' : $file->extension();
        $name = (string) Str::uuid();
        $originalFilename = $name.'.'.$extension;
        $previewFilename = $name.'.jpg';
        $disk = Storage::disk(config('filesystems.default'));

        $disk->putFileAs('photos/originals', $file, $originalFilename);

        try {
            $preview = ImagePreview::jpeg($file->get());
            $disk->put('photos/previews/'.$previewFilename, $preview);
        } catch (RuntimeException) {
            $disk->delete('photos/originals/'.$originalFilename);

            throw ValidationException::withMessages([
                'photo' => 'Не получилось подготовить превью для этого файла.',
            ]);
        }

        try {
            return DB::transaction(function () use ($asHero, $originalFilename, $previewFilename, $stem) {
                if ($asHero) {
                    Photo::query()->where('is_hero', true)->update(['is_hero' => false]);
                }

                return Photo::query()->create([
                    'original_filename' => $originalFilename,
                    'preview_filename' => $previewFilename,
                    'alt' => $asHero ? 'Портрет Киры Новиковой' : ($stem !== '' ? $stem : 'Фото'),
                    'is_hero' => $asHero,
                    'position' => ((int) Photo::query()->max('position')) + 1,
                ]);
            });
        } catch (\Throwable $exception) {
            $disk->delete([
                'photos/originals/'.$originalFilename,
                'photos/previews/'.$previewFilename,
            ]);

            throw $exception;
        }
    }
}
