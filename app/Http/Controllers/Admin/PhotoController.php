<?php

namespace App\Http\Controllers\Admin;

use App\Actions\StorePortfolioPhoto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePhotoRequest;
use App\Models\Photo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PhotoController extends Controller
{
    public function index(): View
    {
        return view('admin.photos.index', [
            'photos' => Photo::query()->orderBy('position')->orderBy('id')->get(),
        ]);
    }

    public function store(StorePhotoRequest $request, StorePortfolioPhoto $store): RedirectResponse
    {
        $store->handle($request->file('photo'), $request->boolean('is_hero'));

        return back()->with('status', 'Фото загружено. На сайте уже превью, оригинал откроется по клику.');
    }

    public function hero(Photo $photo): RedirectResponse
    {
        DB::transaction(function () use ($photo): void {
            Photo::query()->where('is_hero', true)->update(['is_hero' => false]);
            $photo->update([
                'is_hero' => true,
                'alt' => 'Портрет Киры Новиковой',
            ]);
        });

        return back()->with('status', 'Это фото теперь портрет на главной.');
    }

    public function move(Photo $photo, string $direction): RedirectResponse
    {
        $neighbor = $direction === 'up'
            ? Photo::query()->where('position', '<', $photo->position)->orderByDesc('position')->orderByDesc('id')->first()
            : Photo::query()->where('position', '>', $photo->position)->orderBy('position')->orderBy('id')->first();

        if ($neighbor instanceof Photo) {
            $current = $photo->position;
            $photo->update(['position' => $neighbor->position]);
            $neighbor->update(['position' => $current]);
        }

        return back();
    }

    public function destroy(Photo $photo): RedirectResponse
    {
        Storage::disk(config('filesystems.default'))->delete([
            $photo->originalPath(),
            $photo->previewPath(),
        ]);

        $photo->delete();

        return back()->with('status', 'Фото удалено.');
    }
}
