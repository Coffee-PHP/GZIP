<?php

/**
 * GzipCompressionMethodInterface.php
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

namespace CoffeePhp\Gzip\Contract;

use CoffeePhp\CompressionMethod\Contract\CompressionMethodInterface;

/**
 * Interface GzipCompressionMethodInterface
 * @package coffeephp\gzip
 * @author Danny Damsky <dannydamsky99@gmail.com>
 * @since 2020-09-19
 */
interface GzipCompressionMethodInterface extends CompressionMethodInterface
{
    /**
     * @var string
     */
    public const EXTENSION_GZIP = 'gz';

    /**
     * @var string
     */
    public const EXTENSION_ARCHIVE = 'tar';

    /**
     * @var string
     */
    public const EXTENSION_GZIPPED_ARCHIVE = self::EXTENSION_ARCHIVE . '.' . self::EXTENSION_GZIP;

    /**
     * The level of compression.
     * Can be given as 0 for no compression up to 9
     * for maximum compression.
     *
     * 6 is the optimal value in most circumstances,
     * as the differences in compression are minimal
     * after 6 but the differences in performance
     * are substantial.
     *
     * @var int
     */
    public const DEFAULT_COMPRESSION_LEVEL = 6;
}
