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
 */

declare(strict_types=1);

namespace CoffeePhp\Gzip\Test\Unit;

use CoffeePhp\FileSystem\Contract\Data\Path\DirectoryInterface;
use CoffeePhp\FileSystem\Contract\Data\Path\FileInterface;
use CoffeePhp\FileSystem\Data\Path\PathNavigator;
use CoffeePhp\FileSystem\FileManager;
use CoffeePhp\Gzip\GzipCompressionMethod;
use CoffeePhp\Tarball\TarballCompressionMethod;
use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

/**
 * Class GzipCompressionMethodTest
 * @package coffeephp\gzip
 * @author Danny Damsky <dannydamsky99@gmail.com>
 * @since 2020-09-19
 * @see GzipCompressionMethod
 */
final class GzipCompressionMethodTest extends TestCase
{
    private Generator $faker;
    private FileManager $fileManager;
    private GzipCompressionMethod $gzip;
    private DirectoryInterface $testDirectory;
    private FileInterface $testFile;
    private string $uniqueString;

    /**
     * GzipCompressionMethodTest constructor.
     * @param string|null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->faker = Factory::create();
        $this->fileManager = new FileManager();
        $this->gzip = new GzipCompressionMethod(
            $this->fileManager,
            new TarballCompressionMethod($this->fileManager)
        );
    }

    /**
     * @inheritDoc
     * @noinspection PhpUndefinedMethodInspection
     */
    protected function setUp(): void
    {
        parent::setUp();
        $testDirectoryPath = (new PathNavigator(__DIR__))->abc();
        $testFilePath = (clone $testDirectoryPath)
            ->def()->ghi()->jkl()->mno()->pqr()->stu()->vwx()->yz()->down('file.txt');
        $this->testDirectory = $this->fileManager->createDirectory($testDirectoryPath);
        $this->testFile = $this->fileManager->createFile($testFilePath);

        // Generate unique string.
        $uniqueString = '';
        for ($i = 0; $i < $this->faker->numberBetween(50, 9000); ++$i) {
            $uniqueString .= $this->faker->realText();
            $uniqueString .= $this->faker->md5;
            $uniqueString .= $this->faker->regexify('[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}');
        }
        $this->uniqueString = $uniqueString;

        $this->testFile->write($this->uniqueString);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->testDirectory->delete();
    }

    /**
     * @see GzipCompressionMethod::compressPath()
     * @see GzipCompressionMethod::uncompressPath()
     */
    public function testPathCompression(): void
    {
        $gzippedDirectory = $this->gzip->compressPath($this->testDirectory);

        assertSame(
            "{$this->testDirectory}.tar.gz",
            (string)$gzippedDirectory
        );

        $this->testDirectory->delete();

        $this->gzip->uncompressPath($gzippedDirectory);

        assertTrue(
            $this->testDirectory->exists() &&
            $this->testFile->exists() &&
            $this->testFile->read() === $this->uniqueString
        );

        $gzippedDirectory->delete();

        $gzippedFile = $this->gzip->compressPath($this->testFile);

        assertSame(
            "{$this->testFile}.gz",
            (string)$gzippedFile
        );

        $this->testFile->delete();

        $this->gzip->uncompressPath($gzippedFile);

        assertTrue($this->testFile->exists() && $this->testFile->read() === $this->uniqueString);

        $gzippedFile->delete();
    }

    /**
     * @see GzipCompressionMethod::compressDirectory()
     * @see GzipCompressionMethod::uncompressDirectory()
     */
    public function testDirectoryCompression(): void
    {
        $gzippedDirectory = $this->gzip->compressDirectory($this->testDirectory);

        assertSame(
            "{$this->testDirectory}.tar.gz",
            (string)$gzippedDirectory
        );

        $this->testDirectory->delete();

        $this->gzip->uncompressDirectory($gzippedDirectory);

        assertTrue(
            $this->testDirectory->exists() &&
            $this->testFile->exists() &&
            $this->testFile->read() === $this->uniqueString
        );

        $gzippedDirectory->delete();
    }

    /**
     * @see GzipCompressionMethod::compressFile()
     * @see GzipCompressionMethod::uncompressFile()
     */
    public function testFileCompression(): void
    {
        $gzippedFile = $this->gzip->compressFile($this->testFile);

        assertSame(
            "{$this->testFile}.gz",
            (string)$gzippedFile
        );

        $this->testFile->delete();

        $this->gzip->uncompressFile($gzippedFile);

        assertTrue($this->testFile->exists() && $this->testFile->read() === $this->uniqueString);

        $gzippedFile->delete();
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
