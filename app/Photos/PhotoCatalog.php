<?php

namespace App\Photos;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use JsonException;

class PhotoCatalog
{
    private const PATH = 'photos/catalog.json';

    /**
     * @return Collection<int, PortfolioPhoto>
     */
    public function all(): Collection
    {
        return $this->photos()
            ->sortBy([['position', 'asc'], ['id', 'asc']])
            ->values();
    }

    public function find(string $id): ?PortfolioPhoto
    {
        return $this->all()->first(fn (PortfolioPhoto $photo): bool => $photo->id === $id);
    }

    public function updatedAt(): int
    {
        $payload = $this->payload();

        return is_numeric($payload['updated_at'] ?? null) ? (int) $payload['updated_at'] : 0;
    }

    public function add(PortfolioPhoto $photo): void
    {
        $photos = $this->photos();

        if ($photo->isHero) {
            $photos = $photos->map(fn (PortfolioPhoto $item): PortfolioPhoto => $item->withHero(false));
        }

        $this->write($photos->push($photo));
    }

    public function makeHero(PortfolioPhoto $photo): void
    {
        $this->write($this->photos()->map(function (PortfolioPhoto $item) use ($photo): PortfolioPhoto {
            if ($item->id === $photo->id) {
                return $item->withHero(true, 'Портрет Киры Новиковой');
            }

            return $item->isHero ? $item->withHero(false) : $item;
        }));
    }

    public function move(PortfolioPhoto $photo, string $direction): void
    {
        $photos = $this->all()->values();
        $index = $photos->search(fn (PortfolioPhoto $item): bool => $item->id === $photo->id);
        $neighborIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index === false || ! $photos->has($neighborIndex)) {
            return;
        }

        $current = $photos[$index];
        $neighbor = $photos[$neighborIndex];
        $photos[$index] = $current->withPosition($neighbor->position);
        $photos[$neighborIndex] = $neighbor->withPosition($current->position);

        $this->write($photos);
    }

    public function remove(PortfolioPhoto $photo): void
    {
        $this->write($this->photos()->reject(fn (PortfolioPhoto $item): bool => $item->id === $photo->id));
    }

    /**
     * @return Collection<int, PortfolioPhoto>
     */
    private function photos(): Collection
    {
        $photos = $this->payload()['photos'] ?? [];

        if (! is_array($photos)) {
            return collect();
        }

        return collect($photos)
            ->map(fn (mixed $photo): ?PortfolioPhoto => is_array($photo) ? PortfolioPhoto::fromArray($photo) : null)
            ->filter()
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        $disk = $this->disk();

        if (! $disk->exists(self::PATH)) {
            return [];
        }

        try {
            $decoded = json_decode($disk->get(self::PATH), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  Collection<int, PortfolioPhoto>  $photos
     */
    private function write(Collection $photos): void
    {
        $this->disk()->put(self::PATH, json_encode([
            'updated_at' => time(),
            'photos' => $photos->map(fn (PortfolioPhoto $photo): array => $photo->toArray())->values()->all(),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }

    private function disk(): Filesystem
    {
        return Storage::disk(config('filesystems.default'));
    }
}
