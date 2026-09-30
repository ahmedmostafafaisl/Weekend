<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * php artisan security:check
 *
 * Reviews the deploy-time settings that the code review cannot see (they live
 * in the server's .env). Exit code 1 when a critical issue is found, so it can
 * gate a deploy script.
 */
class SecurityCheck extends Command
{
    protected $signature = 'security:check';

    protected $description = 'Check production security settings (.env / config) and public/ uploads';

    public function handle(): int
    {
        $critical = 0;
        $row = function (string $level, string $check, string $detail) use (&$critical) {
            if ($level === 'FAIL') {
                $critical++;
            }
            $this->line(sprintf('  %s  %-28s %s', ['OK' => '<info>✔ OK  </info>', 'WARN' => '<comment>! WARN</comment>', 'FAIL' => '<error>✘ FAIL</error>'][$level], $check, $detail));
        };

        $prod = app()->environment('production');
        $this->info('Environment: '.app()->environment());

        $row(config('app.debug') && $prod ? 'FAIL' : (config('app.debug') ? 'WARN' : 'OK'),
            'APP_DEBUG', config('app.debug') ? 'on — stack traces, SQL and config exposed on errors' : 'off');

        $row(str_starts_with((string) config('app.url'), 'https://') ? 'OK' : ($prod ? 'FAIL' : 'WARN'),
            'APP_URL uses https', (string) config('app.url'));

        $row(config('session.secure') ? 'OK' : ($prod ? 'FAIL' : 'WARN'),
            'Secure session cookie', config('session.secure') ? 'yes' : 'no — admin session can travel over http');

        $row(config('app.key') ? 'OK' : 'FAIL', 'APP_KEY set', config('app.key') ? 'yes' : 'missing');

        $proxies = (string) config('app.trusted_proxies');
        $row($proxies === '' ? 'WARN' : 'OK', 'TRUSTED_PROXIES',
            $proxies === '' ? 'none — correct ONLY if no proxy/LB/Cloudflare sits in front' : $proxies);

        $row(config('sanctum.expiration') ? 'OK' : 'WARN', 'API token expiry',
            config('sanctum.expiration') ? config('sanctum.expiration').' min' : 'never — stolen tokens stay valid');

        $origins = (array) config('cors.allowed_origins');
        $row(in_array('*', $origins, true) ? 'WARN' : 'OK', 'CORS origins', implode(', ', $origins));

        $row(config('services.geidea.webhook_secret') ? 'OK' : 'WARN', 'Geidea webhook secret',
            config('services.geidea.webhook_secret') ? 'set' : 'unset — callbacks re-verified via API (slower, still safe)');

        foreach (['geidea.api_key' => 'Geidea', 'tabby.secret_key' => 'Tabby', 'tamara.api_token' => 'Tamara', 'maysar.api_key' => 'Maysar'] as $key => $name) {
            $row(config("services.$key") ? 'OK' : 'WARN', "$name credentials", config("services.$key") ? 'set' : 'unset — gateway unusable');
        }

        // Executable or scriptable files in upload folders (see SafeUpload / §6).
        $suspicious = [];
        foreach (['Ads', 'unites', 'users', 'department', 'Packages', 'homepage'] as $dir) {
            $path = public_path($dir);
            if (! is_dir($path)) {
                continue;
            }
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if (preg_match('/\.(php\d?|phtml|phar|phps|pht|svg|html?|xhtml|shtml|htaccess)$/i', $file->getFilename())) {
                    $suspicious[] = str_replace(public_path().'/', '', $file->getPathname());
                }
            }
        }
        $row($suspicious ? 'FAIL' : 'OK', 'Upload folders', $suspicious
            ? count($suspicious).' executable/scriptable file(s) — investigate before deleting'
            : 'no executable or scriptable files');
        foreach (array_slice($suspicious, 0, 20) as $f) {
            $this->line("        <error>$f</error>");
        }

        $this->newLine();
        $critical
            ? $this->error("$critical critical issue(s).")
            : $this->info('No critical issues.');

        return $critical ? self::FAILURE : self::SUCCESS;
    }
}
