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
use CoffeePhp\Di\Contract\ContainerInterface;
use CoffeePhp\Gzip\Contract\GzipCompressionMethodInterface;
use CoffeePhp\Gzip\GzipCompressionMethod;

/**
 * Class GzipComponentRegistrar
 * @package coffeephp\gzip
 * @author Danny Damsky <dannydamsky99@gmail.com>
 * @since 2020-09-19
 */
final class GzipComponentRegistrar implements ComponentRegistrarInterface
{
    /**
     * GzipComponentRegistrar constructor.
     * @param ContainerInterface $di
     */
    public function __construct(private ContainerInterface $di)
    {
    }

    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->di->bind(GzipCompressionMethod::class, GzipCompressionMethod::class);
        $this->di->bind(GzipCompressionMethodInterface::class, GzipCompressionMethod::class);
    }
}
