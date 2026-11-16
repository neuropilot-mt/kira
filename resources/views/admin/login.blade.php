@extends('admin.layout', ['title' => 'Вход'])

@section('content')
    <h1>Вход</h1>
    <p class="lead">Здесь загружаются оригиналы. На сайте остаётся лёгкое превью, полный кадр открывается по клику.</p>

    @if ($errors->any())
        <div class="errors">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form class="login" method="POST" action="{{ route('admin.login.store') }}">
        @csrf
        <label>
            <span>Почта</span>
            <input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required>
        </label>
        <label>
            <span>Пароль</span>
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <button class="solid" type="submit">Войти</button>
    </form>
@endsection
