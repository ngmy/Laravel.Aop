<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\ValueObjects;

final class SourceMapFile extends \SplFileInfo
{
    /**
     * The source map filename.
     *
     * @var string
     */
    private const SOURCE_MAP_FILENAME = 'source_map.ser';

    /**
     * Create a new instance.
     *
     * @param CompiledPath $compiledPath The compiled path
     */
    public function __construct(
        CompiledPath $compiledPath,
    ) {
        parent::__construct($compiledPath->getPathname().'/'.self::SOURCE_MAP_FILENAME);
    }
}
