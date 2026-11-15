<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Project rule: modules are connected only through the registries. Core code
 * (everything in app/ outside app/Modules) must never use a module directly,
 * and modules must not use each other. bootstrap/providers.php lists the module
 * service providers; that is the only place that names them.
 */
class ArchitectureTest extends TestCase
{
    /** @return iterable<string> */
    private function phpFiles(string $directory): iterable
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                yield $file->getPathname();
            }
        }
    }

    public function test_core_code_does_not_use_modules_directly(): void
    {
        $app = dirname(__DIR__, 2).'/app';

        foreach ($this->phpFiles($app) as $path) {
            if (str_starts_with($path, $app.'/Modules/')) {
                continue;
            }

            $this->assertStringNotContainsString('App\\Modules\\', file_get_contents($path), "{$path} uses a module directly; go through a registry.");
        }
    }

    public function test_modules_do_not_use_each_other(): void
    {
        $modules = dirname(__DIR__, 2).'/app/Modules';

        foreach (glob($modules.'/*', GLOB_ONLYDIR) as $moduleDir) {
            $module = basename($moduleDir);

            foreach ($this->phpFiles($moduleDir) as $path) {
                preg_match_all('/App\\\\Modules\\\\(\w+)/', file_get_contents($path), $matches);

                foreach (array_unique($matches[1]) as $used) {
                    $this->assertSame($module, $used, "{$path} (module {$module}) uses module {$used}; go through a registry.");
                }
            }
        }
    }
}
