@extends('admin.layout', ['title' => 'Фото'])

@section('content')
    <h1>Фото</h1>
    <p class="lead">Загрузите оригинал. Файл с именем hero или отмеченный портретом встаёт в большой кадр на главной. Остальные идут в избранные работы.</p>

    @if ($errors->any())
        <div class="errors">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form class="upload" method="POST" action="{{ route('admin.photos.store') }}" enctype="multipart/form-data">
        @csrf
        <label class="file">
            <span class="quiet">Оригинал, до 40 МБ. JPEG, PNG, WebP или GIF.</span>
            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp,image/gif" required>
        </label>
        <label class="check">
            <input type="checkbox" name="is_hero" value="1">
            Портрет на главной
        </label>
        <div class="actions">
            <button class="solid" type="submit">Загрузить</button>
        </div>
    </form>

    @if ($photos->isEmpty())
        <p class="empty">Пока пусто. После загрузки обновите сайт: карточки возьмут превью сами.</p>
    @else
        <ol class="photos">
            @foreach ($photos as $photo)
                <li>
                    <img src="{{ url($photo->previewPath()) }}" alt="{{ $photo->alt }}">
                    <div>
                        <h2>
                            {{ $photo->alt }}
                            @if ($photo->isHero)
                                <span class="mark">Портрет</span>
                            @endif
                        </h2>
                        <div class="actions">
                            @unless ($photo->isHero)
                                <form method="POST" action="{{ route('admin.photos.hero', $photo->id) }}">
                                    @csrf
                                    <button class="line" type="submit">Сделать портретом</button>
                                </form>
                            @endunless
                            <form method="POST" action="{{ route('admin.photos.move', [$photo->id, 'up']) }}">
                                @csrf
                                <button class="line" type="submit">Выше</button>
                            </form>
                            <form method="POST" action="{{ route('admin.photos.move', [$photo->id, 'down']) }}">
                                @csrf
                                <button class="line" type="submit">Ниже</button>
                            </form>
                            <form method="POST" action="{{ route('admin.photos.destroy', $photo->id) }}" onsubmit="return confirm('Удалить это фото?')">
                                @csrf
                                @method('DELETE')
                                <button class="line" type="submit">Удалить</button>
                            </form>
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
@endsection
