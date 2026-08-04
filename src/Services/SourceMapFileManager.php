<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Services;

use Illuminate\Support\Facades\File;
use Ngmy\LaravelAop\Collections\SourceMap;
use Ngmy\LaravelAop\ValueObjects\SourceMapFile;

final class SourceMapFileManager
{
    /**
     * Get the source map from the source map file.
     *
     * @param SourceMapFile $sourceMapFile The source map file
     *
     * @return SourceMap The source map
     */
    public function get(SourceMapFile $sourceMapFile): SourceMap
    {
        $contents = File::get($sourceMapFile->getPathname());

        try {
            $sourceMap = unserialize($contents);
        } catch (\ErrorException $e) {
            throw new \RuntimeException(\sprintf(
                'Failed to unserialize the source map file "%s". It may be corrupted; run "php artisan aop:compile" to regenerate it.',
                $sourceMapFile->getPathname(),
            ), previous: $e);
        }

        if (!$sourceMap instanceof SourceMap) {
            throw new \RuntimeException(\sprintf(
                'The source map file "%s" does not contain a valid source map; run "php artisan aop:compile" to regenerate it.',
                $sourceMapFile->getPathname(),
            ));
        }

        return $sourceMap;
    }

    /**
     * Write the source map to the source map file.
     *
     * The write is performed atomically by writing to a temporary file first and then renaming it into place, so
     * that a process interrupted mid-write can never leave a corrupted source map file behind.
     *
     * @param SourceMapFile $sourceMapFile The source map file
     * @param SourceMap     $sourceMap     The source map
     */
    public function put(SourceMapFile $sourceMapFile, SourceMap $sourceMap): void
    {
        $contents = serialize($sourceMap);
        $pathName = $sourceMapFile->getPathname();
        $tempPathName = $pathName.'.'.uniqid('', true).'.tmp';

        try {
            File::put($tempPathName, $contents);
            File::move($tempPathName, $pathName);
        } finally {
            File::delete($tempPathName);
        }
    }
}
