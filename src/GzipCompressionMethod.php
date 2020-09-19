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
use CoffeePhp\FileSystem\Contract\Data\Path\DirectoryInterface;
use CoffeePhp\FileSystem\Contract\Data\Path\FileInterface;
use CoffeePhp\FileSystem\Contract\Data\Path\PathInterface;
use CoffeePhp\FileSystem\Contract\Data\Path\PathNavigatorInterface;
use CoffeePhp\FileSystem\Contract\FileManagerInterface;
use CoffeePhp\FileSystem\Enum\AccessMode;
use CoffeePhp\FileSystem\Enum\PathConflictStrategy;
use CoffeePhp\FileSystem\Exception\FileSystemException;
use CoffeePhp\Gzip\Contract\GzipCompressionMethodInterface;
use CoffeePhp\Gzip\Exception\GzipCompressException;
use CoffeePhp\Gzip\Exception\GzipUncompressException;
use CoffeePhp\Tarball\Contract\TarballCompressionMethodInterface;
use Throwable;

use function gzclose;
use function gzdecode;
use function gzencode;
use function gzeof;
use function gzopen;
use function gzread;
use function gzwrite;
use function is_file;
use function unlink;

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
     * @var TarballCompressionMethodInterface
     */
    private TarballCompressionMethodInterface $tarballCompressionMethod;

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
    private int $compressionLevel;

    /**
     * The amount of bytes to read from a file
     * when compressing/uncompressing to/from GZIP.
     *
     * @var int
     */
    private int $gzipBytesToRead;

    /**
     * GzipCompressionMethod constructor.
     *
     * @param FileManagerInterface $fileManager
     *
     * @param TarballCompressionMethodInterface $tarballCompressionMethod
     *
     * @param PathConflictStrategy|null $pathConflictStrategy
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
        FileManagerInterface $fileManager,
        TarballCompressionMethodInterface $tarballCompressionMethod,
        ?PathConflictStrategy $pathConflictStrategy = null,
        int $compressionLevel = self::DEFAULT_COMPRESSION_LEVEL,
        int $gzipBytesToRead = self::GZIP_BYTES_TO_READ
    ) {
        parent::__construct($fileManager, $pathConflictStrategy);
        $this->tarballCompressionMethod = $tarballCompressionMethod;
        $this->compressionLevel = $compressionLevel;
        $this->gzipBytesToRead = $gzipBytesToRead;
    }

    /**
     * @inheritDoc
     */
    public function compressPath(PathInterface $uncompressedPath): FileInterface
    {
        if ($uncompressedPath->isDirectory()) {
            /** @var DirectoryInterface $uncompressedPath */
            return $this->compressDirectory($uncompressedPath);
        }

        if ($uncompressedPath->isFile()) {
            /** @var FileInterface $uncompressedPath */
            return $this->compressFile($uncompressedPath);
        }

        throw new GzipCompressException("The provided path does not exist: {$uncompressedPath}");
    }

    /**
     * @inheritDoc
     */
    public function uncompressPath(FileInterface $compressedPath): PathInterface
    {
        $absolutePath = (string)$compressedPath;

        if ($this->isFullPath($absolutePath, self::EXTENSION_GZIPPED_ARCHIVE)) {
            return $this->uncompressDirectory($compressedPath);
        }

        if ($this->isFullPath($absolutePath, self::EXTENSION_GZIP)) {
            return $this->uncompressFile($compressedPath);
        }

        throw new FileSystemException(
            "Failed to uncompress path: {$absolutePath} ; Reason: Unknown file extension provided."
        );
    }

    /**
     * @inheritDoc
     */
    public function compressDirectory(DirectoryInterface $uncompressedDirectory): FileInterface
    {
        try {
            if (!$uncompressedDirectory->exists()) {
                throw new GzipCompressException("The given directory does not exist: {$uncompressedDirectory}");
            }
            $archive = $this->tarballCompressionMethod->compressDirectory($uncompressedDirectory);
            $gzippedArchive = $this->compressFile($archive);
            $archive->delete();
            return $gzippedArchive;
        } catch (GzipCompressException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new GzipCompressException(
                "Unexpected Compression Exception: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function uncompressDirectory(FileInterface $compressedDirectory): DirectoryInterface
    {
        try {
            $absolutePath = (string)$compressedDirectory;
            if (!$compressedDirectory->exists()) {
                throw new GzipUncompressException("The given archive does not exist: {$compressedDirectory}");
            }
            $extension = self::EXTENSION_GZIPPED_ARCHIVE;
            if (!$this->isFullPath($absolutePath, $extension)) {
                throw new GzipUncompressException(
                    "Directory archive {$absolutePath} does not have the extension: {$extension}"
                );
            }
            $archive = $this->uncompressFile($compressedDirectory);
            $directory = $this->tarballCompressionMethod->uncompressDirectory($archive);
            $archive->delete();
            return $directory;
        } catch (GzipUncompressException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new GzipUncompressException(
                "Unexpected Uncompression Exception: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function compressFile(FileInterface $file): FileInterface
    {
        try {
            $absolutePath = (string)$file;
            if (!$file->exists()) {
                throw new GzipCompressException("The given file does not exist: {$absolutePath}");
            }
            $extension = self::EXTENSION_GZIP;
            $fullPath = $this->getFullPath($absolutePath, $extension);
            $pathNavigator = $this->getAvailablePath($fullPath);
            return $this->handleLowLevelCompression($file, $pathNavigator);
        } catch (GzipCompressException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new GzipCompressException(
                "Unexpected Compression Exception: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * @param FileInterface $file
     * @param PathNavigatorInterface $destination
     * @return FileInterface
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @psalm-suppress MixedMethodCall
     * @psalm-suppress UndefinedVariable
     * @psalm-suppress MixedArgument
     */
    private function handleLowLevelCompression(FileInterface $file, PathNavigatorInterface $destination): FileInterface
    {
        try {
            $fileStream = $file->getStream();
            $fileStream->open(AccessMode::READ());
            $gzipStream = gzopen((string)$destination, "wb{$this->compressionLevel}");
            if ($gzipStream === false) {
                throw new GzipCompressException("Failed to open GZIP stream in path: {$destination}");
            }
            foreach ($fileStream->readBytes($this->gzipBytesToRead) as $chunk) {
                gzwrite($gzipStream, $chunk);
            }
            $fileStream->close();
            gzclose($gzipStream);
            unset($fileStream, $gzipStream);
            return $this->fileManager->getFile($destination);
        } finally {
            if (isset($fileStream) && $fileStream->isOpen()) {
                $fileStream->close();
                unset($fileStream);
            }
            if (isset($gzipStream) && $gzipStream !== false) { // @phpstan-ignore-line
                gzclose($gzipStream);
                unset($gzipStream);
                if (is_file((string)$destination)) {
                    unlink((string)$destination);
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function uncompressFile(FileInterface $compressedFile): FileInterface
    {
        try {
            $absolutePath = (string)$compressedFile;
            if (!$compressedFile->exists()) {
                throw new GzipUncompressException("The given compressed file does not exist: {$compressedFile}");
            }
            $extension = self::EXTENSION_GZIP;
            if (!$this->isFullPath($absolutePath, $extension)) {
                throw new GzipUncompressException(
                    "File {$absolutePath} does not have the extension: {$extension}"
                );
            }
            $originalPath = $this->getOriginalPath($absolutePath, $extension);
            $pathNavigator = $this->getAvailablePath($originalPath);
            return $this->handleLowLevelUncompression($compressedFile, $pathNavigator);
        } catch (GzipUncompressException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new GzipUncompressException(
                "Unexpected Uncompression Exception: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * @param FileInterface $compressedFile
     * @param PathNavigatorInterface $destination
     * @return FileInterface
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @psalm-suppress MixedMethodCall
     * @psalm-suppress UndefinedVariable
     * @psalm-suppress MixedArgument
     */
    private function handleLowLevelUncompression(
        FileInterface $compressedFile,
        PathNavigatorInterface $destination
    ): FileInterface {
        try {
            $gzipStream = gzopen((string)$compressedFile, 'rb');
            if ($gzipStream === false) {
                throw new GzipUncompressException("Failed to open GZIP stream in path: {$compressedFile}");
            }
            $file = $this->fileManager->createFile($destination);
            $fileStream = $file->getStream();
            $fileStream->open(AccessMode::APPEND());
            while (!gzeof($gzipStream)) {
                $fileStream->append(
                    gzread($gzipStream, $this->gzipBytesToRead),
                    false
                );
            }
            gzclose($gzipStream);
            $fileStream->close();
            unset($gzipStream, $fileStream);
            return $file;
        } finally {
            if (isset($gzipStream) && $gzipStream !== false) { // @phpstan-ignore-line
                gzclose($gzipStream);
                unset($gzipStream);
            }
            if (isset($fileStream) && $fileStream->isOpen()) { // @phpstan-ignore-line
                $fileStream->close();
                unset($fileStream);
                if (is_file((string)$destination)) {
                    unlink((string)$destination);
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function compressString(string $string): string
    {
        $encodedString = gzencode($string, $this->compressionLevel);
        if ($encodedString === false) {
            throw new GzipCompressException("Failed to GZIP compress string: {$string}");
        }
        return $encodedString;
    }

    /**
     * @inheritDoc
     */
    public function uncompressString(string $string): string
    {
        $decodedString = gzdecode($string);
        if ($decodedString === false) {
            throw new GzipUncompressException("Failed to GZIP uncompress string: {$string}");
        }
        return $decodedString;
    }
}
