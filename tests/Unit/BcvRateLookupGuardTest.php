<?php

namespace Tests\Unit;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class BcvRateLookupGuardTest extends TestCase
{
    public function test_app_code_never_picks_the_latest_bcv_rate_without_a_date(): void
    {
        $latest = [];
        $unordered = [];

        foreach ([app_path(), base_path('routes')] as $dir) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

            foreach ($iterator as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $src = file_get_contents($file->getPathname());
                if ($src === false) {
                    continue;
                }

                $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());

                if (
                    str_contains($src, "latest('date_effective')")
                    || str_contains($src, 'latest("date_effective")')
                ) {
                    $latest[] = $relative;
                }

                if (
                    str_contains($src, "orderByDesc('date_effective')")
                    && ! str_ends_with($file->getPathname(), 'BcvRate.php')
                ) {
                    $unordered[] = $relative;
                }
            }
        }

        $this->assertSame([], $latest);
        $this->assertSame([], $unordered);
    }
}
