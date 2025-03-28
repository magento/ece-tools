<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Filesystem;

use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;

/**
 * Directory path configurations.
 */
class DirectoryList
{
    /**
     * Keys of directory configuration.
     */
    private const PATH = 'path';

    /**
     * Directory codes.
     */
    public const DIR_INIT = 'init';
    public const DIR_VAR = 'var';
    public const DIR_LOG = 'log';
    public const DIR_GENERATED_CODE = 'code';
    public const DIR_GENERATED_METADATA = 'metadata';
    public const DIR_ETC = 'etc';
    public const DIR_MEDIA = 'media';
    public const DIR_VIEW_PREPROCESSED = 'view-preprocessed';
    public const DIR_STATIC = 'static';

    /**
     * @var string
     */
    private $root;

    /**
     * @var string
     */
    private $magentoRoot;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param SystemList $systemList
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(SystemList $systemList, MagentoVersion $magentoVersion)
    {
        $this->root = $systemList->getRoot();
        $this->magentoRoot = $systemList->getMagentoRoot();
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * Gets a filesystem path of a directory.
     *
     * @param string $code
     * @param bool $relativePath
     * @return string
     * @throws UndefinedPackageException
     */
    public function getPath(string $code, bool $relativePath = false): string
    {
        $magentoRoot = $relativePath ? '' : $this->getMagentoRoot();
        $directories = $this->getDefaultDirectories();

        if (!array_key_exists($code, $directories)) {
            $directories = $this->getDefaultVariadicDirectories();
        }

        if (!array_key_exists($code, $directories)) {
            throw  new \RuntimeException("Code {$code} is not registered");
        }

        if (!array_key_exists(self::PATH, $directories[$code])) {
            throw new \RuntimeException(
                sprintf('Config var "%s" does not exists', self::PATH)
            );
        }

        $path = $directories[$code][self::PATH];

        return $magentoRoot . ($magentoRoot && $path ? '/' : '') . $path;
    }

    /**
     * @return string
     */
    public function getRoot(): string
    {
        return $this->root;
    }

    /**
     * @return string
     */
    public function getMagentoRoot(): string
    {
        return $this->magentoRoot;
    }

    /**
     * @return string
     * @throws UndefinedPackageException
     */
    public function getInit(): string
    {
        return $this->getPath(self::DIR_INIT);
    }

    /**
     * @return string
     * @throws UndefinedPackageException
     */
    public function getVar(): string
    {
        return $this->getPath(self::DIR_VAR);
    }

    /**
     * @return string
     * @throws UndefinedPackageException
     */
    public function getLog(): string
    {
        return $this->getPath(self::DIR_LOG);
    }

    /**
     * @return string
     * @throws UndefinedPackageException
     */
    public function getGeneratedCode(): string
    {
        return $this->getPath(self::DIR_GENERATED_CODE);
    }

    /**
     * @return string
     * @throws UndefinedPackageException
     */
    public function getGeneratedMetadata(): string
    {
        return $this->getPath(self::DIR_GENERATED_METADATA);
    }

    /**
     * Retrieves writable directories.
     *
     * @return array
     * @throws UndefinedPackageException
     */
    public function getWritableDirectories(): array
    {
        $writableDirs = [
            self::DIR_ETC,
            self::DIR_MEDIA,
            self::DIR_LOG,
            self::DIR_VIEW_PREPROCESSED,
        ];

        if ($this->magentoVersion->satisfies('2.1.*')) {
            $writableDirs[] = self::DIR_GENERATED_METADATA;
            $writableDirs[] = self::DIR_GENERATED_CODE;
        }

        return array_map(function ($path) {
            return $this->getPath($path, true);
        }, $writableDirs);
    }

    /**
     * Retrieves mount points for writable directories.
     *
     * @return array
     * @throws UndefinedPackageException
     */
    public function getMountPoints(): array
    {
        $mountPoints = [
            self::DIR_ETC,
            self::DIR_VAR,
            self::DIR_MEDIA,
            self::DIR_STATIC
        ];

        return array_map(function ($path) {
            return $this->getPath($path, true);
        }, $mountPoints);
    }

    /**
     * @return array
     */
    private function getDefaultDirectories(): array
    {
        $config = [
            self::DIR_INIT => [self::PATH => 'init'],
            self::DIR_VAR => [self::PATH => 'var'],
            self::DIR_LOG => [self::PATH => 'var/log'],
            self::DIR_ETC => [self::PATH => 'app/etc'],
            self::DIR_MEDIA => [self::PATH => 'pub/media'],
            self::DIR_STATIC => [self::PATH => 'pub/static'],
            self::DIR_VIEW_PREPROCESSED => [self::PATH => 'var/view_preprocessed'],
        ];

        return $config;
    }

    /**
     * @return array
     * @throws UndefinedPackageException
     */
    private function getDefaultVariadicDirectories(): array
    {
        $config = [];

        if ($this->magentoVersion->satisfies('2.1.*')) {
            $config[self::DIR_GENERATED_CODE] = [self::PATH => 'var/generation'];
            $config[self::DIR_GENERATED_METADATA] = [self::PATH => 'var/di'];
        } else {
            $config[self::DIR_GENERATED_CODE] = [self::PATH => 'generated/code'];
            $config[self::DIR_GENERATED_METADATA] = [self::PATH => 'generated/metadata'];
        }

        return $config;
    }

    /**
     * @return string
     */
    public function getPatches(): string
    {
        return $this->getRoot() . '/patches';
    }

    /**
     * @return string
     */
    public function getViews(): string
    {
        return $this->getRoot() . '/views';
    }

    /**
     * @return string
     */
    public function getDockerRoot(): string
    {
        return $this->getMagentoRoot() . '/.docker';
    }
}
