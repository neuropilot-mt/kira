<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortfolioController extends Controller
{
    public function home(): Response
    {
        return response(file_get_contents(resource_path('site/index.html')), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    public function manifest(): JsonResponse
    {
        $photos = Photo::query()->orderBy('position')->orderBy('id')->get();
        $hero = $photos->firstWhere('is_hero', true);
        $works = $photos
            ->reject(fn (Photo $photo): bool => $hero instanceof Photo && $photo->is($hero))
            ->values();

        return response()->json([
            'version' => $photos->max('updated_at')?->getTimestamp() ?? 0,
            'hero' => $hero instanceof Photo ? $this->payload($hero) : null,
            'works' => $works->map(fn (Photo $photo): array => $this->payload($photo))->all(),
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
    private function payload(Photo $photo): array
    {
        return [
            'preview' => $photo->previewPath(),
            'original' => $photo->originalPath(),
            'alt' => $photo->alt,
        ];
    }
}
