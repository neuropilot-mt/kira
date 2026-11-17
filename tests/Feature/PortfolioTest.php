<?php

namespace Tests\Feature;

use App\Photos\PhotoCatalog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortfolioTest extends TestCase
{
    public function test_homepage_is_the_existing_portfolio(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Кира Новикова', false)
            ->assertSee('site.js', false);
    }

    public function test_manifest_lists_the_hero_and_the_other_photos(): void
    {
        Storage::fake();
        Storage::disk(config('filesystems.default'))->put('photos/catalog.json', json_encode([
            'updated_at' => 10,
            'photos' => [
                [
                    'id' => 'portrait',
                    'original_filename' => 'portrait.jpg',
                    'preview_filename' => 'portrait-preview.jpg',
                    'alt' => 'Портрет Киры Новиковой',
                    'is_hero' => true,
                    'position' => 1,
                ],
                [
                    'id' => 'look',
                    'original_filename' => 'look.jpg',
                    'preview_filename' => 'look-preview.jpg',
                    'alt' => 'Look',
                    'is_hero' => false,
                    'position' => 2,
                ],
            ],
        ]));

        $this->get('/photos/photos.json')
            ->assertOk()
            ->assertHeader('cache-control', 'no-store, private')
            ->assertJsonPath('hero.original', 'photos/originals/portrait.jpg')
            ->assertJsonPath('hero.preview', 'photos/previews/portrait-preview.jpg')
            ->assertJsonPath('works.0.original', 'photos/originals/look.jpg')
            ->assertJsonPath('works.0.preview', 'photos/previews/look-preview.jpg');
    }

    public function test_guest_cannot_open_the_admin(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_admin_login_uses_the_configured_password(): void
    {
        config([
            'admin.email' => 'admin@example.com',
            'admin.password' => 'secret',
        ]);

        $this->post(route('admin.login.store'), [
            'email' => 'admin@example.com',
            'password' => 'wrong',
        ])->assertSessionHasErrors('email');

        $this->post(route('admin.login.store'), [
            'email' => 'admin@example.com',
            'password' => 'secret',
        ])->assertRedirect(route('admin.photos.index'));
    }

    public function test_admin_upload_stores_the_original_and_a_smaller_preview(): void
    {
        Storage::fake();

        $this->withSession(['admin' => true])
            ->post(route('admin.photos.store'), [
                'photo' => UploadedFile::fake()->image('hero.jpg', 1800, 2200),
                'is_hero' => '1',
            ])
            ->assertRedirect();

        $photo = app(PhotoCatalog::class)->all()->first();
        $disk = Storage::disk(config('filesystems.default'));

        $this->assertNotNull($photo);
        $this->assertTrue($photo->isHero);
        $this->assertSame('Портрет Киры Новиковой', $photo->alt);
        $this->assertTrue($disk->exists($photo->originalPath()));
        $this->assertTrue($disk->exists($photo->previewPath()));
        $this->assertTrue($disk->exists('photos/catalog.json'));

        $preview = getimagesizefromstring($disk->get($photo->previewPath()));
        $original = getimagesizefromstring($disk->get($photo->originalPath()));

        $this->assertIsArray($preview);
        $this->assertIsArray($original);
        $this->assertLessThanOrEqual(1600, max($preview[0], $preview[1]));
        $this->assertSame(2200, max($original[0], $original[1]));

        $this->get('/photos/photos.json')
            ->assertJsonPath('hero.preview', $photo->previewPath())
            ->assertJsonPath('hero.original', $photo->originalPath());

        $this->get('/'.$photo->previewPath())->assertOk();
        $this->get('/'.$photo->originalPath())->assertOk();
    }
}
