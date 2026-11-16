<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['original_filename', 'preview_filename', 'alt', 'is_hero', 'position'])]
class Photo extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_hero' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function originalPath(): string
    {
        return 'photos/originals/'.$this->original_filename;
    }

    public function previewPath(): string
    {
        return 'photos/previews/'.$this->preview_filename;
    }
}
