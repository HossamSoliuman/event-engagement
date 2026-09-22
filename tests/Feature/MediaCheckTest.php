<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaCheckTest extends TestCase
{
    public function test_check_passes_when_disk_is_writable_and_publicly_readable(): void
    {
        $disk = Storage::fake('media', ['url' => 'https://cdn.test']);
        Http::fake(fn (Request $request) => Http::response(
            $disk->get(ltrim(parse_url($request->url(), PHP_URL_PATH), '/'))
        ));

        $this->artisan('media:check')
            ->expectsOutputToContain('ok    write')
            ->expectsOutputToContain('ok    public fetch')
            ->expectsOutputToContain('Disk "media" is working.')
            ->assertSuccessful();

        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://cdn.test/healthcheck/'));
        $this->assertSame([], $disk->allFiles('healthcheck'), 'probe file must be cleaned up');
    }

    public function test_check_fails_when_anonymous_read_is_denied(): void
    {
        $disk = Storage::fake('media', ['url' => 'https://cdn.test']);
        Http::fake(['cdn.test/*' => Http::response('<Error>AccessDenied</Error>', 403)]);

        $this->artisan('media:check')
            ->expectsOutputToContain('HTTP 403')
            ->expectsOutputToContain('public-read bucket policy')
            ->expectsOutputToContain('Disk "media" is NOT fully working.')
            ->assertFailed();

        $this->assertSame([], $disk->allFiles('healthcheck'), 'probe file must be cleaned up even on failure');
    }

    public function test_check_fails_when_public_url_serves_different_content(): void
    {
        Storage::fake('media', ['url' => 'https://cdn.test']);
        Http::fake(['cdn.test/*' => Http::response('stale cached body')]);

        $this->artisan('media:check')
            ->expectsOutputToContain('body does not match')
            ->assertFailed();
    }

    public function test_skip_http_does_not_fetch_the_public_url(): void
    {
        Storage::fake('media');
        Http::fake();

        $this->artisan('media:check', ['--skip-http' => true])->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_check_fails_when_disk_is_not_writable(): void
    {
        $disk = Storage::fake('media');
        $disk->put('healthcheck', 'a file where the probe directory should go');
        Http::fake();

        $this->artisan('media:check')
            ->expectsOutputToContain('FAIL  write')
            ->expectsOutputToContain('AWS_ACCESS_KEY_ID')
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_check_fails_when_disk_cannot_be_opened(): void
    {
        Storage::forgetDisk('media');
        config(['filesystems.disks.media' => ['driver' => 'local', 'root' => __FILE__, 'throw' => false]]);

        $this->artisan('media:check')
            ->expectsOutputToContain('FAIL  open disk')
            ->assertFailed();
    }

    public function test_check_fails_for_unknown_disk(): void
    {
        $this->artisan('media:check', ['--disk' => 'nope'])
            ->expectsOutput('Disk "nope" is not configured.')
            ->assertFailed();
    }
}
