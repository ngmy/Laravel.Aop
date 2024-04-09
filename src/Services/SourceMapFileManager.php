<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Services;

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
        $contents = file_get_contents($sourceMapFile->getPathname());

        if (false === $contents) {
            throw new \RuntimeException("Failed to read the source map file: {$sourceMapFile->getPathname()}");
        }

        /** @var SourceMap $sourceMap */
        $sourceMap = unserialize($contents);

        return $sourceMap;
    }

    /**
     * Write the source map to the source map file.
     *
     * @param SourceMapFile $sourceMapFile The source map file
     * @param SourceMap     $sourceMap     The source map
     */
    public function put(SourceMapFile $sourceMapFile, SourceMap $sourceMap): void
    {
        $contents = serialize($sourceMap);

        $result = file_put_contents($sourceMapFile->getPathname(), $contents);

        if (false === $result) {
            throw new \RuntimeException("Failed to write the source map file: {$sourceMapFile->getPathname()}");
        }
    }
}
