<?php

/**
 * GzipComponentRegistrar.php
 *
 * Copyright 2020 Danny Damsky
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package coffeephp\gzip
 * @author Danny Damsky <dannydamsky99@gmail.com>
 * @since 2020-09-19
 */

declare(strict_types=1);

namespace CoffeePhp\Gzip\Integration;

use CoffeePhp\ComponentRegistry\Contract\ComponentRegistrarInterface;
use CoffeePhp\Compress\Contract\CompressorInterface;
use CoffeePhp\Compress\Contract\FileCompressorInterface;
use CoffeePhp\CompressionMethod\Contract\CompressionMethodInterface;
use CoffeePhp\CompressionMethod\Contract\DirectoryCompressionMethodInterface;
use CoffeePhp\CompressionMethod\Contract\PathCompressionMethodInterface;
use CoffeePhp\CompressionMethod\Contract\StringCompressionMethodInterface;
use CoffeePhp\Di\Contract\ContainerInterface;
use CoffeePhp\Gzip\Contract\GzipCompressionMethodInterface;
use CoffeePhp\Gzip\GzipCompressionMethod;
use CoffeePhp\Uncompress\Contract\UncompressorInterface;

/**
 * Class GzipComponentRegistrar
 * @package coffeephp\gzip
 * @author Danny Damsky <dannydamsky99@gmail.com>
 * @since 2020-09-19
 */
final class GzipComponentRegistrar implements ComponentRegistrarInterface
{

    /**
     * @inheritDoc
     */
    public function register(ContainerInterface $di): void
    {
        $di->bind(CompressorInterface::class, GzipCompressionMethodInterface::class);
        $di->bind(UncompressorInterface::class, GzipCompressionMethodInterface::class);

        $di->bind(StringCompressionMethodInterface::class, GzipCompressionMethodInterface::class);
        $di->bind(PathCompressionMethodInterface::class, GzipCompressionMethodInterface::class);
        $di->bind(FileCompressorInterface::class, GzipCompressionMethodInterface::class);
        $di->bind(DirectoryCompressionMethodInterface::class, GzipCompressionMethodInterface::class);
        $di->bind(CompressionMethodInterface::class, GzipCompressionMethodInterface::class);

        $di->bind(GzipCompressionMethodInterface::class, GzipCompressionMethod::class);
        $di->bind(GzipCompressionMethod::class, GzipCompressionMethod::class);
    }
}
