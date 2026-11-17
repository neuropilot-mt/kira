<?php

namespace App\Http\Controllers;

use App\Photos\PhotoCatalog;
use App\Photos\PortfolioPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortfolioController extends Controller
{
    public function __construct(private PhotoCatalog $catalog) {}

    public function home(): Response
    {
        return response(file_get_contents(resource_path('site/index.html')), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    public function manifest(): JsonResponse
    {
        $photos = $this->catalog->all();
        $hero = $photos->first(fn (PortfolioPhoto $photo): bool => $photo->isHero);
        $works = $photos
            ->reject(fn (PortfolioPhoto $photo): bool => $hero instanceof PortfolioPhoto && $photo->id === $hero->id)
            ->values();

        return response()->json([
            'version' => $this->catalog->updatedAt(),
            'hero' => $hero instanceof PortfolioPhoto ? $this->payload($hero) : null,
            'works' => $works->map(fn (PortfolioPhoto $photo): array => $this->payload($photo))->all(),
        ])->header('Cache-Control', 'no-store');
    }

    public function file(string $directory, string $filename): StreamedResponse
    {
        $path = 'photos/'.$directory.'/'.$filename;
        $disk = Storage::disk(config('filesystems.default'));
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * @return array{preview: string, original: string, alt: string}
     */
    private function payload(PortfolioPhoto $photo): array
    {
        return [
            'preview' => $photo->previewPath(),
            'original' => $photo->originalPath(),
            'alt' => $photo->alt,
        ];
    }
}
