<?php

namespace App\Http\Controllers\Admin;

use App\Actions\StorePortfolioPhoto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePhotoRequest;
use App\Photos\PhotoCatalog;
use App\Photos\PortfolioPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PhotoController extends Controller
{
    public function __construct(private PhotoCatalog $catalog) {}

    public function index(): View
    {
        return view('admin.photos.index', [
            'photos' => $this->catalog->all(),
        ]);
    }

    public function store(StorePhotoRequest $request, StorePortfolioPhoto $store): RedirectResponse
    {
        $store->handle($request->file('photo'), $request->boolean('is_hero'));

        return back()->with('status', 'Фото загружено. На сайте уже превью, оригинал откроется по клику.');
    }

    public function hero(string $photo): RedirectResponse
    {
        $this->catalog->makeHero($this->photo($photo));

        return back()->with('status', 'Это фото теперь портрет на главной.');
    }

    public function move(string $photo, string $direction): RedirectResponse
    {
        $this->catalog->move($this->photo($photo), $direction);

        return back();
    }

    public function destroy(string $photo): RedirectResponse
    {
        $record = $this->photo($photo);

        $this->catalog->remove($record);
        Storage::disk(config('filesystems.default'))->delete([
            $record->originalPath(),
            $record->previewPath(),
        ]);

        return back()->with('status', 'Фото удалено.');
    }

    private function photo(string $id): PortfolioPhoto
    {
        return $this->catalog->find($id) ?? abort(404);
    }
}
