<?php

/**
 * GzipComponentRegistrarTest.php
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

namespace CoffeePhp\Gzip\Test\Integration;

use CoffeePhp\ComponentRegistry\ComponentRegistry;
use CoffeePhp\Di\Container;
use CoffeePhp\Gzip\Contract\GzipCompressionMethodInterface;
use CoffeePhp\Gzip\GzipCompressionMethod;
use CoffeePhp\Gzip\Integration\GzipComponentRegistrar;
use CoffeePhp\Tarball\Integration\TarballComponentRegistrar;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

/**
 * Class GzipComponentRegistrarTest
 * @package coffeephp\gzip
 * @author Danny Damsky <dannydamsky99@gmail.com>
 * @since 2020-09-19
 * @see GzipComponentRegistrar
 */
final class GzipComponentRegistrarTest extends TestCase
{
    /**
     * @see GzipComponentRegistrar::register()
     */
    public function testRegister(): void
    {
        $di = new Container();
        $componentRegistry = new ComponentRegistry($di);
        $componentRegistry->register(TarballComponentRegistrar::class);
        $componentRegistry->register(GzipComponentRegistrar::class);

        assertTrue($di->has(GzipCompressionMethodInterface::class));
        assertTrue($di->has(GzipCompressionMethod::class));

        assertInstanceOf(
            GzipCompressionMethod::class,
            $di->get(GzipCompressionMethod::class)
        );
        assertSame(
            $di->get(GzipCompressionMethod::class),
            $di->get(GzipCompressionMethodInterface::class)
        );
        assertSame(
            $di->get(GzipCompressionMethod::class),
            $di->get(GzipCompressionMethod::class)
        );
    }
}
