<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('admin') === true) {
            return redirect()->route('admin.photos.index');
        }

        return view('admin.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $email = Str::lower($request->string('email')->toString());
        $password = $request->string('password')->toString();
        $expectedEmail = Str::lower((string) config('admin.email'));
        $expectedPassword = (string) config('admin.password');

        $valid = $expectedEmail !== ''
            && $expectedPassword !== ''
            && hash_equals($expectedEmail, $email)
            && hash_equals($expectedPassword, $password);

        if (! $valid) {
            throw ValidationException::withMessages([
                'email' => 'Неверная почта или пароль.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->put('admin', true);

        return redirect()->intended(route('admin.photos.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget('admin');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
