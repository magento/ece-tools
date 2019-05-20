<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
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
    const PATH = 'path';

    /**
     * Directory codes.
     */
    const DIR_INIT = 'init';
    const DIR_VAR = 'var';
    const DIR_LOG = 'log';
    const DIR_GENERATED_CODE = 'code';
    const DIR_GENERATED_METADATA = 'metadata';
    const DIR_ETC = 'etc';
    const DIR_MEDIA = 'media';
    const DIR_VIEW_PREPROCESSED = 'view-preprocessed';
    const DIR_STATIC = 'static';

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

        if (!array_key_exists(static::PATH, $directories[$code])) {
            throw new \RuntimeException(
                sprintf('Config var "%s" does not exists', static::PATH)
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
        return $this->getPath(static::DIR_INIT);
    }

    /**
     * @return string
     * @throws UndefinedPackageException
     */
    public function getVar(): string
    {
        return $this->getPath(static::DIR_VAR);
    }

    /**
     * @return string
     * @throws UndefinedPackageException
     */
    public function getLog(): string
    {
        return $this->getPath(static::DIR_LOG);
    }

    /**
     * @return string
     * @throws UndefinedPackageException
     */
    public function getGeneratedCode(): string
    {
        return $this->getPath(static::DIR_GENERATED_CODE);
    }

    /**
     * @return string
     * @throws UndefinedPackageException
     */
    public function getGeneratedMetadata(): string
    {
        return $this->getPath(static::DIR_GENERATED_METADATA);
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
            static::DIR_ETC,
            static::DIR_MEDIA,
            static::DIR_LOG,
            static::DIR_VIEW_PREPROCESSED,
        ];

        if ($this->magentoVersion->satisfies('2.1.*')) {
            $writableDirs[] = static::DIR_GENERATED_METADATA;
            $writableDirs[] = static::DIR_GENERATED_CODE;
        }

        return array_map(function ($path) {
            return $this->getPath($path, true);
        }, $writableDirs);
    }

    /**
     * @return array
     */
    private function getDefaultDirectories(): array
    {
        $config = [
            static::DIR_INIT => [static::PATH => 'init'],
            static::DIR_VAR => [static::PATH => 'var'],
            static::DIR_LOG => [static::PATH => 'var/log'],
            static::DIR_ETC => [static::PATH => 'app/etc'],
            static::DIR_MEDIA => [static::PATH => 'pub/media'],
            static::DIR_STATIC => [static::PATH => 'pub/static'],
            static::DIR_VIEW_PREPROCESSED => [static::PATH => 'var/view_preprocessed'],
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
            $config[static::DIR_GENERATED_CODE] = [static::PATH => 'var/generation'];
            $config[static::DIR_GENERATED_METADATA] = [static::PATH => 'var/di'];
        } else {
            $config[static::DIR_GENERATED_CODE] = [static::PATH => 'generated/code'];
            $config[static::DIR_GENERATED_METADATA] = [static::PATH => 'generated/metadata'];
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
