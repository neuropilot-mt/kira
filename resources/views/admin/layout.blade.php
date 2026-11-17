<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — Кира Новикова</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('admin.css') }}">
</head>
<body>
    <div class="shell">
        <header class="top">
            <a class="brand" href="{{ route('admin.photos.index') }}">
                <strong>Кира Новикова</strong>
                <span>Админка</span>
            </a>
            @if (session('admin'))
                <nav>
                    <a href="{{ url('/') }}">Открыть сайт</a>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button class="ghost" type="submit">Выйти</button>
                    </form>
                </nav>
            @endif
        </header>

        <main class="sheet">
            @if (session('status'))
                <p class="notice">{{ session('status') }}</p>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
