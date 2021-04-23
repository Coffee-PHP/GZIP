<?php

/**
 * GzipCompressionMethodTest.php
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
 * @noinspection StaticInvocationViaThisInspection
 */

declare(strict_types=1);

namespace CoffeePhp\Gzip\Test\Unit;

use CoffeePhp\Gzip\GzipCompressionMethod;
use CoffeePhp\QualityTools\TestCase;
use CoffeePhp\Tarball\TarballCompressionMethod;

use function file_get_contents;
use function is_dir;
use function is_file;
use function PHPUnit\Framework\assertSame;
use function rmdir;
use function unlink;

use const DIRECTORY_SEPARATOR;

/**
 * Class GzipCompressionMethodTest
 * @package coffeephp\gzip
 * @author Danny Damsky <dannydamsky99@gmail.com>
 * @since 2020-09-19
 * @see GzipCompressionMethod
 */
final class GzipCompressionMethodTest extends TestCase
{
    private GzipCompressionMethod $gzip;
    private string $testDirectory;
    private string $testFile;
    private string $uniqueString;

    /**
     * @before
     */
    public function setupDependencies(): void
    {
        $this->gzip = new GzipCompressionMethod(new TarballCompressionMethod());
        $this->testDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'temp';
        $this->testFile = $this->testDirectory . DIRECTORY_SEPARATOR . 'file.txt';
        $uniqueString = '';
        for ($i = 0; $i < $this->getFaker()->numberBetween(50, 9000); ++$i) {
            $uniqueString .= $this->getFaker()->realText();
            $uniqueString .= $this->getFaker()->md5;
            $uniqueString .= $this->getFaker()->regexify('[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}');
        }
        $this->uniqueString = $uniqueString;
        $this->assertNotFalse(mkdir($this->testDirectory));
        $this->assertNotFalse(file_put_contents($this->testFile, $this->uniqueString));
    }

    /**
     * @after
     */
    public function teardownDependencies(): void
    {
        if (is_file($this->testFile)) {
            $this->assertNotFalse(unlink($this->testFile));
        }
        if (is_dir($this->testDirectory)) {
            $this->assertNotFalse(rmdir($this->testDirectory));
        }
        $this->assertFileDoesNotExist($this->testFile);
        $this->assertDirectoryDoesNotExist($this->testDirectory);
    }

    /**
     * @see GzipCompressionMethod::compressDirectory()
     * @see GzipCompressionMethod::uncompressDirectory()
     */
    public function testDirectoryCompression(): void
    {
        $gzippedDirectory = $this->gzip->compressDirectory($this->testDirectory);
        $this->assertFileExists($gzippedDirectory);
        assertSame("$this->testDirectory.tar.gz", $gzippedDirectory);

        $this->teardownDependencies();

        $directory = $this->gzip->uncompressDirectory($gzippedDirectory);
        $this->assertSame($directory, $this->testDirectory);

        $this->assertDirectoryExists($this->testDirectory);
        $this->assertFileExists($this->testFile);
        $this->assertSame($this->uniqueString, file_get_contents($this->testFile));

        $this->assertNotFalse(unlink($gzippedDirectory));
        $this->assertFileDoesNotExist($gzippedDirectory);
    }

    /**
     * @see GzipCompressionMethod::compressFile()
     * @see GzipCompressionMethod::uncompressFile()
     */
    public function testFileCompression(): void
    {
        $gzippedFile = $this->gzip->compressFile($this->testFile);
        $this->assertFileExists($gzippedFile);
        assertSame("$this->testFile.gz", $gzippedFile);

        $this->assertNotFalse(unlink($this->testFile));
        $this->assertFileDoesNotExist($this->testFile);

        $file = $this->gzip->uncompressFile($gzippedFile);
        $this->assertSame($file, $this->testFile);

        $this->assertFileExists($this->testFile);
        $this->assertSame($this->uniqueString, file_get_contents($this->testFile));

        $this->assertNotFalse(unlink($gzippedFile));
        $this->assertFileDoesNotExist($gzippedFile);
    }

    /**
     * @see GzipCompressionMethod::compressString()
     * @see GzipCompressionMethod::uncompressString()
     */
    public function testStringCompression(): void
    {
        assertSame(
            $this->uniqueString,
            $this->gzip->uncompressString(
                $this->gzip->compressString($this->uniqueString)
            )
        );
    }
}
