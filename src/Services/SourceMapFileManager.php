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

        File::put($sourceMapFile->getPathname(), $contents);
    }
}
