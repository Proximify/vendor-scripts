<?php

/**
 * @author    Proximify Inc <support@proximify.com>
 * @copyright Copyright (c) 2020, Proximify Inc
 * @license   MIT
 */

namespace Proximify;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class VendorScripts extends CLIActions
{
    const TRUSTED_NAMESPACE = 'Proximify';
    const TRUSTED_VENDOR = 'proximify';

    /**
     * @inheritDoc
     */
    public function runComposerAction(array $options): void
    {
        $env = $options[self::ENV_OPTIONS];
        $action = $env['action'];
        $vendorDir = $env['vendor-dir'] ?? false;

        if (!$vendorDir) {
            return;
        }

        $vendorDir .= '/' . self::TRUSTED_VENDOR;

        if (!is_dir($vendorDir)) {
            return;
        }

        echo "\n Action: $action\n";

        $finder = new Finder();

        $finder->depth('== 0');

        // find all directories in the vendor directory
        $finder->directories()->in($vendorDir);

        foreach ($finder as $dir) {
            $path = $dir->getRealPath();
            // $name = $dir->getRelativePathname();
            echo "\n$path\n";

            if ($this->hasScript($action, $path)) {
                // call the composer script manually with composer run-script $action
                echo "\nrunning...\n";
                $output = $this->runScript($action, $path);
                print_r($output);
                echo "\nDone!\n";
            }
        }
    }

    public function getScript(string $action, string $packageDir)
    {
        $path = $packageDir . '/composer.json';

        if (!is_file($path))
            return false;

        $str = file_get_contents($path) ?? '';
        $scripts = json_decode($str, true)['scripts'] ?? [];

        return $scripts[$action] ?? null;
    }

    public function hasScript(string $action, string $packageDir): bool
    {
        return $this->getScript($action, $packageDir);
    }

    public function runScript($action, $packageDir)
    {
        $cmd = ['composer', 'run-script', $action];

        $process = new Process($cmd, $packageDir);

        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }
}
