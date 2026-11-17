<?php

namespace App\Photos;

final class PortfolioPhoto
{
    public function __construct(
        public readonly string $id,
        public readonly string $originalFilename,
        public readonly string $previewFilename,
        public readonly string $alt,
        public readonly bool $isHero,
        public readonly int $position,
    ) {}

    public function originalPath(): string
    {
        return 'photos/originals/'.$this->originalFilename;
    }

    public function previewPath(): string
    {
        return 'photos/previews/'.$this->previewFilename;
    }

    public function withHero(bool $isHero, ?string $alt = null): self
    {
        return new self(
            $this->id,
            $this->originalFilename,
            $this->previewFilename,
            $alt ?? $this->alt,
            $isHero,
            $this->position,
        );
    }

    public function withPosition(int $position): self
    {
        return new self(
            $this->id,
            $this->originalFilename,
            $this->previewFilename,
            $this->alt,
            $this->isHero,
            $position,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): ?self
    {
        if (! is_string($data['id'] ?? null) || $data['id'] === '') {
            return null;
        }

        if (! is_string($data['original_filename'] ?? null) || ! is_string($data['preview_filename'] ?? null)) {
            return null;
        }

        return new self(
            $data['id'],
            $data['original_filename'],
            $data['preview_filename'],
            is_string($data['alt'] ?? null) && $data['alt'] !== '' ? $data['alt'] : 'Фото',
            (bool) ($data['is_hero'] ?? false),
            (int) ($data['position'] ?? 0),
        );
    }

    /**
     * @return array{id: string, original_filename: string, preview_filename: string, alt: string, is_hero: bool, position: int}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'original_filename' => $this->originalFilename,
            'preview_filename' => $this->previewFilename,
            'alt' => $this->alt,
            'is_hero' => $this->isHero,
            'position' => $this->position,
        ];
    }
}
