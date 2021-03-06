<?php

namespace PhpSchool\WorkshopManager;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

class Filesystem extends SymfonyFilesystem
{
    /**
     * Execute a callback in a directory, callback is passed the
     * absolute path.
     *
     * @param string $path
     * @param callable $callback
     */
    public function executeInPath($path, callable $callback)
    {
        $currentPath = getcwd();

        if (!$this->exists($path)) {
            throw new IOException(sprintf('Path: "%s" does not exist.', $path));
        }

        chdir($path);
        $callback($path);
        chdir($currentPath);
    }

    /**
     * @param string $path
     * @return bool
     */
    public function isLink($path)
    {
        return is_link($path);
    }

    /**
     * @param string $path
     * @return bool
     */
    public function isWritable($path)
    {
        return is_writable($path);
    }
}
