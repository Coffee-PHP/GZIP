<?php

/**
 * GzipCompressionMethod.php
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

namespace CoffeePhp\Gzip;

use CoffeePhp\CompressionMethod\AbstractCompressionMethod;
use CoffeePhp\Gzip\Contract\GzipCompressionMethodInterface;
use CoffeePhp\Gzip\Exception\GzipCompressException;
use CoffeePhp\Gzip\Exception\GzipUncompressException;
use CoffeePhp\Tarball\Contract\TarballCompressionMethodInterface;
use Throwable;

use function fclose;
use function feof;
use function fopen;
use function fread;
use function fwrite;
use function gzclose;
use function gzdecode;
use function gzencode;
use function gzeof;
use function gzopen;
use function gzread;
use function gzwrite;
use function is_file;
use function pathinfo;
use function unlink;

use const DIRECTORY_SEPARATOR;

/**
 * Class GzipCompressionMethod
 * @package coffeephp\gzip
 * @author Danny Damsky <dannydamsky99@gmail.com>
 * @since 2020-09-19
 */
final class GzipCompressionMethod extends AbstractCompressionMethod implements GzipCompressionMethodInterface
{
    /**
     * @var int
     */
    private const GZIP_BYTES_TO_READ = 524288; // 512 KiB

    /**
     * GzipCompressionMethod constructor.
     *
     * @param TarballCompressionMethodInterface $tarballCompressionMethod
     *
     * @param int $compressionLevel
     * The level of compression.
     * Can be given as 0 for no compression up to 9
     * for maximum compression.
     *
     * 6 is the optimal value in most circumstances,
     * as the differences in compression are minimal
     * after 6 but the differences in performance
     * are substantial.
     *
     * @param int $gzipBytesToRead
     * The amount of bytes to read from a file
     * when compressing/uncompressing to/from GZIP.
     */
    public function __construct(
        private TarballCompressionMethodInterface $tarballCompressionMethod,
        private int $compressionLevel = self::DEFAULT_COMPRESSION_LEVEL,
        private int $gzipBytesToRead = self::GZIP_BYTES_TO_READ
    ) {
    }

    /**
     * @inheritDoc
     */
    public function compressDirectory(string $uncompressedDirectoryPath): string
    {
        try {
            $archive = $this->tarballCompressionMethod->compressDirectory($uncompressedDirectoryPath);
            return $this->compressFile($archive);
        } catch (GzipCompressException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new GzipCompressException($e->getMessage(), (int)$e->getCode(), $e);
        } finally {
            try {
                isset($archive) && @unlink($archive);
            } catch (Throwable) {
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function uncompressDirectory(string $compressedDirectoryFilePath): string
    {
        try {
            $archive = $this->uncompressFile($compressedDirectoryFilePath);
            return $this->tarballCompressionMethod->uncompressDirectory($archive);
        } catch (GzipUncompressException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new GzipUncompressException($e->getMessage(), (int)$e->getCode(), $e);
        } finally {
            try {
                isset($archive) && @unlink($archive);
            } catch (Throwable) {
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function compressFile(string $uncompressedFilePath): string
    {
        if (!is_file($uncompressedFilePath)) {
            throw new GzipCompressException('The given uncompressed file is invalid or does not exist');
        }
        $destination = $this->getAvailablePath($uncompressedFilePath . '.' . self::EXTENSION_GZIP);
        try {
            $this->handleCompression($uncompressedFilePath, $destination);
            return $destination;
        } catch (GzipCompressException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new GzipCompressException($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * @psalm-suppress RedundantCondition, TypeDoesNotContainType
     */
    private function handleCompression(string $target, string $destination): void
    {
        try {
            $fileStream = fopen($target, 'rb');
            if ($fileStream === false) {
                throw new GzipCompressException('Failed to open uncompressed file stream');
            }
            $gzipStream = gzopen($destination, "wb{$this->compressionLevel}");
            if ($gzipStream === false) {
                throw new GzipCompressException('Failed to open GZIP stream');
            }
            while (!feof($fileStream)) {
                $chunk = fread($fileStream, $this->gzipBytesToRead);
                if ($chunk === false) {
                    throw new GzipCompressException('Failed to read data from the uncompressed file stream');
                }
                if (gzwrite($gzipStream, $chunk) === false) {
                    throw new GzipCompressException('Failed to write data to the GZIP stream');
                }
            }
        } finally {
            try {
                if (isset($fileStream) && $fileStream !== false) {
                    fclose($fileStream);
                    unset($fileStream);
                }
            } catch (Throwable) {
            }
            try {
                if (isset($gzipStream) && $gzipStream !== false) {
                    gzclose($gzipStream);
                    unset($gzipStream);
                }
            } catch (Throwable) {
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function uncompressFile(string $compressedFilePath): string
    {
        if (!is_file($compressedFilePath)) {
            throw new GzipCompressException('The given GZIP file is invalid or does not exist');
        }
        $pathInfo = pathinfo($compressedFilePath);
        if (($pathInfo['extension'] ?? '') !== self::EXTENSION_GZIP) {
            throw new GzipUncompressException('The given file is not a gzipped file');
        }
        $destination = $this->getAvailablePath($pathInfo['dirname'] . DIRECTORY_SEPARATOR . $pathInfo['filename']);
        try {
            $this->handleUncompression($compressedFilePath, $destination);
            return $destination;
        } catch (GzipUncompressException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new GzipUncompressException($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * @psalm-suppress RedundantCondition
     */
    private function handleUncompression(string $target, string $destination): void
    {
        try {
            $gzipStream = gzopen($target, 'rb');
            if ($gzipStream === false) {
                throw new GzipUncompressException('Failed to open GZIP stream');
            }
            $fileStream = fopen($destination, 'ab');
            if ($fileStream === false) {
                throw new GzipUncompressException('Failed to open the uncompressed file stream for writing');
            }
            while (!gzeof($gzipStream)) {
                $chunk = gzread($gzipStream, $this->gzipBytesToRead);
                if ($chunk === false) {
                    throw new GzipUncompressException('Failed to read data from the compressed GZIP file stream');
                }
                if (fwrite($fileStream, $chunk) === false) {
                    throw new GzipUncompressException('Failed to write uncompressed  data to the file stream');
                }
            }
        } finally {
            try {
                if (isset($gzipStream) && $gzipStream !== false) {
                    gzclose($gzipStream);
                    unset($gzipStream);
                }
            } catch (Throwable) {
            }
            try {
                if (isset($fileStream) && $fileStream !== false) {
                    fclose($fileStream);
                    unset($fileStream);
                }
            } catch (Throwable) {
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function compressString(string $string): string
    {
        try {
            return (string)gzencode($string, $this->compressionLevel);
        } catch (Throwable $e) {
            throw new GzipCompressException($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function uncompressString(string $string): string
    {
        try {
            return (string)gzdecode($string);
        } catch (Throwable $e) {
            throw new GzipUncompressException($e->getMessage(), (int)$e->getCode(), $e);
        }
    }
}
