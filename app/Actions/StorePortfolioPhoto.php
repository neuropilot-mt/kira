<?php

namespace App\Actions;

use App\Photos\PhotoCatalog;
use App\Photos\PortfolioPhoto;
use App\Support\ImagePreview;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class StorePortfolioPhoto
{
    public function __construct(private PhotoCatalog $catalog) {}

    public function handle(UploadedFile $file, bool $asHero): PortfolioPhoto
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

        $positions = $this->catalog->all()->map(fn (PortfolioPhoto $photo): int => $photo->position);
        $photo = new PortfolioPhoto(
            $name,
            $originalFilename,
            $previewFilename,
            $asHero ? 'Портрет Киры Новиковой' : ($stem !== '' ? $stem : 'Фото'),
            $asHero,
            ($positions->max() ?? 0) + 1,
        );

        try {
            $this->catalog->add($photo);
        } catch (\Throwable $exception) {
            $disk->delete([
                'photos/originals/'.$originalFilename,
                'photos/previews/'.$previewFilename,
            ]);

            throw $exception;
        }

        return $photo;
    }
}
