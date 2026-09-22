<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class CheckMediaDisk extends Command
{
    protected $signature = 'media:check
        {--disk=media : Disk to probe (defaults to the "media" alias the app uses)}
        {--skip-http : Do not fetch the public URL of the probe file}';

    protected $description = 'Write, read, publicly fetch and delete a probe file to verify the media disk (e.g. S3) is fully working';

    private bool $failed = false;

    public function handle(): int
    {
        $diskName = (string) $this->option('disk');
        $config = (array) config("filesystems.disks.{$diskName}");

        if ($config === []) {
            $this->error("Disk \"{$diskName}\" is not configured.");

            return self::FAILURE;
        }

        $this->describeDisk($diskName, $config);

        try {
            $disk = Storage::disk($diskName);
        } catch (Throwable $e) {
            $this->fail('open disk', $diskName, $e->getMessage());

            return self::FAILURE;
        }

        $path = 'healthcheck/'.Str::random(32).'.txt';
        $payload = 'media:check '.now()->toIso8601String().' '.Str::random(16);

        try {
            if (! $this->step('write', $path, fn () => $disk->put($path, $payload))) {
                $this->hint('Check AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY, AWS_BUCKET and AWS_DEFAULT_REGION, and that the key may s3:PutObject on this bucket.');

                return self::FAILURE;
            }

            $this->step('exists', $path, fn () => $disk->exists($path));
            $this->step('read back', $path, fn () => $disk->get($path) === $payload);

            if (! $this->option('skip-http')) {
                $this->checkPublicUrl($disk, $path, $payload);
            }
        } finally {
            $this->step('delete', $path, fn () => $disk->delete($path) && ! $disk->exists($path));
        }

        $this->newLine();

        if ($this->failed) {
            $this->error("Disk \"{$diskName}\" is NOT fully working.");

            return self::FAILURE;
        }

        $this->info("Disk \"{$diskName}\" is working.");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function describeDisk(string $diskName, array $config): void
    {
        $driver = (string) ($config['driver'] ?? 'unknown');
        $details = $driver === 's3'
            ? sprintf(
                'bucket %s, region %s%s',
                $config['bucket'] ?: '(unset)',
                $config['region'] ?: '(unset)',
                ! empty($config['endpoint']) ? ', endpoint '.$config['endpoint'] : ''
            )
            : 'root '.($config['root'] ?? '(unset)');

        $this->info(sprintf('Disk "%s" -> driver %s, %s', $diskName, $driver, $details));
    }

    private function checkPublicUrl(Filesystem $disk, string $path, string $payload): void
    {
        $url = $disk->url($path);

        if (! preg_match('#^https?://#i', $url)) {
            $url = url($url);
        }

        $this->line("  url   {$url}");

        try {
            $response = Http::timeout(15)->withoutRedirecting()->get($url);
        } catch (Throwable $e) {
            $this->fail('public fetch', $path, $e->getMessage());

            return;
        }

        if ($response->status() !== 200) {
            $this->fail('public fetch', $path, 'HTTP '.$response->status());

            if (in_array($response->status(), [401, 403], true)) {
                $this->hint('The object exists but anonymous reads are denied: the bucket needs a public-read bucket policy (s3:GetObject on '.($this->bucketArn() ?? 'the bucket').'/*) and "Block public access ... policies" switched off.');
            }

            return;
        }

        if ($response->body() !== $payload) {
            $this->fail('public fetch', $path, 'body does not match what was written');

            return;
        }

        $this->line("  ok    public fetch  {$path}");
    }

    private function step(string $label, string $path, callable $probe): bool
    {
        try {
            $ok = (bool) $probe();
            $reason = 'returned false';
        } catch (Throwable $e) {
            $ok = false;
            $reason = $e->getMessage();
        }

        if ($ok) {
            $this->line(sprintf('  ok    %-13s %s', $label, $path));
        } else {
            $this->fail($label, $path, $reason);
        }

        return $ok;
    }

    private function fail(string $label, string $path, string $reason): void
    {
        $this->failed = true;
        $this->error(sprintf('  FAIL  %-13s %s (%s)', $label, $path, $reason));
    }

    private function hint(string $message): void
    {
        $this->comment('        -> '.$message);
    }

    private function bucketArn(): ?string
    {
        $bucket = config('filesystems.disks.'.$this->option('disk').'.bucket');

        return $bucket ? 'arn:aws:s3:::'.$bucket : null;
    }
}
