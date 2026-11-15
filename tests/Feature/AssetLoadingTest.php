<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AssetLoadingTest extends TestCase
{
    private string $publicDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publicDirectory = storage_path('framework/testing/assets-'.bin2hex(random_bytes(8)));
        File::ensureDirectoryExists($this->publicDirectory.'/css');
        File::ensureDirectoryExists($this->publicDirectory.'/js');
        File::ensureDirectoryExists($this->publicDirectory.'/build');
        File::put($this->publicDirectory.'/css/app.css', 'body { color: teal; }');
        File::put($this->publicDirectory.'/js/app.js', 'document.body.dataset.loaded = "true";');

        $this->app->usePublicPath($this->publicDirectory);
        config(['app.use_vite_assets' => false]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->publicDirectory);

        parent::tearDown();
    }

    public function test_an_old_vite_manifest_cannot_remove_the_default_stylesheet(): void
    {
        File::put($this->publicDirectory.'/build/manifest.json', json_encode([
            'resources/js/app.js' => [
                'file' => 'assets/old-app.js',
                'src' => 'resources/js/app.js',
                'isEntry' => true,
            ],
            'resources/css/app.css' => [
                'file' => 'assets/old-app.css',
                'src' => 'resources/css/app.css',
                'isEntry' => true,
            ],
        ]));

        $this->get('/login')
            ->assertOk()
            ->assertSee('/css/app.css', false)
            ->assertSee('/js/app.js', false)
            ->assertDontSee('/build/assets/old-app.js', false);
    }

    public function test_a_leftover_hot_file_cannot_send_default_assets_to_a_stopped_dev_server(): void
    {
        File::put($this->publicDirectory.'/hot', 'http://localhost:5173');

        $this->get('/login')
            ->assertOk()
            ->assertSee('/css/app.css', false)
            ->assertSee('/js/app.js', false)
            ->assertDontSee('localhost:5173', false);
    }

    public function test_static_asset_urls_change_when_the_bundles_are_updated(): void
    {
        $stylesheet = $this->publicDirectory.'/css/app.css';
        $script = $this->publicDirectory.'/js/app.js';
        touch($stylesheet, 1700000000);
        touch($script, 1700000000);
        clearstatcache();

        $this->get('/login')
            ->assertOk()
            ->assertSee('/css/app.css?v=1700000000', false)
            ->assertSee('/js/app.js?v=1700000000', false);

        File::put($stylesheet, 'body { color: blue; }');
        File::put($script, 'document.body.dataset.updated = "true";');
        touch($stylesheet, 1700000001);
        touch($script, 1700000001);
        clearstatcache();

        $this->get('/login')
            ->assertOk()
            ->assertSee('/css/app.css?v=1700000001', false)
            ->assertSee('/js/app.js?v=1700000001', false);
    }

    public function test_vite_assets_are_available_when_explicitly_enabled(): void
    {
        config(['app.use_vite_assets' => true]);

        File::put($this->publicDirectory.'/build/manifest.json', json_encode([
            'resources/js/app.js' => [
                'file' => 'assets/current-app.js',
                'src' => 'resources/js/app.js',
                'isEntry' => true,
                'css' => ['assets/current-app.css'],
            ],
        ]));

        $this->get('/login')
            ->assertOk()
            ->assertSee('/build/assets/current-app.css', false)
            ->assertSee('/build/assets/current-app.js', false)
            ->assertDontSee('/css/app.css?v=', false);
    }
}
