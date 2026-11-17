<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->session()->get('admin') === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:40960'],
            'is_hero' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.required' => 'Выберите фото.',
            'photo.image' => 'Нужен файл изображения.',
            'photo.mimes' => 'Подойдут JPEG, PNG, WebP и GIF.',
            'photo.uploaded' => 'Файл не загрузился. Сервер принимает одно фото до 40 МБ.',
            'photo.max' => 'Файл больше 40 МБ.',
        ];
    }
}
