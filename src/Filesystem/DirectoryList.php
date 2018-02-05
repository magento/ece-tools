<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem;

use Magento\MagentoCloud\Package\MagentoVersion;

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
    const DIR_GENERATED = 'generated';
    const DIR_GENERATED_CODE = 'code';
    const DIR_GENERATED_METADATA = 'metadata';
    const DIR_ETC = 'etc';
    const DIR_MEDIA = 'media';
    const DIR_VIEW_PREPROCESSED = 'view-preprocessed';

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
     * @var array
     */
    private $directories;

    /**
     * @param SystemList $systemList
     * @param MagentoVersion $version
     * @param array $config
     */
    public function __construct(SystemList $systemList, MagentoVersion $version, array $config = [])
    {
        $this->root = $systemList->getRoot();
        $this->magentoRoot = $systemList->getMagentoRoot();
        $this->magentoVersion = $version;
        $this->directories = $config + $this->getDefaultConfig();
    }

    /**
     * Gets a filesystem path of a directory
     *
     * @param string $code
     * @return string
     */
    public function getPath(string $code, bool $relativePath = false): string
    {
        $magentoRoot = $relativePath ? '' : $this->getMagentoRoot();
        $directories = $this->getDirectories();

        if (!array_key_exists($code, $directories)) {
            throw  new \RuntimeException("Code {$code} is not registered");
        }

        if (!array_key_exists(static::PATH, $directories[$code])) {
            throw new \RuntimeException(
                sprintf('Config var "%s" does not exists', static::PATH)
            );
        }

        $path = $directories[$code][self::PATH];
        $normalizedPath = $magentoRoot . ($magentoRoot && $path ? '/' : '') . $path;

        return $normalizedPath;
    }

    /**
     * @return string
     */
    public function getRoot(): string
    {
        return $this->root;
    }

    /**
     * @return array
     */
    public function getDirectories(): array
    {
        return $this->directories;
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
     */
    public function getInit(): string
    {
        return $this->getPath(static::DIR_INIT);
    }

    /**
     * @return string
     */
    public function getVar(): string
    {
        return $this->getPath(static::DIR_VAR);
    }

    /**
     * @return string
     */
    public function getLog(): string
    {
        return $this->getPath(static::DIR_LOG);
    }

    /**
     * @return string
     */
    public function getGenerated(): string
    {
        return $this->getPath(static::DIR_GENERATED);
    }

    /**
     * @return string
     */
    public function getGeneratedCode(): string
    {
        return $this->getPath(static::DIR_GENERATED_CODE);
    }

    /**
     * @return string
     */
    public function getGeneratedMetadata(): string
    {
        return $this->getPath(static::DIR_GENERATED_METADATA);
    }

    /**
     * Retrieves writable directories.
     *
     * @return array
     */
    public function getWritableDirectories(bool $relativePath = false): array
    {
        $writableDirs = [static::DIR_ETC, static::DIR_MEDIA];

        if ($this->magentoVersion->satisfies('2.1.*')) {
            $writableDirs[] = static::DIR_GENERATED_METADATA;
            $writableDirs[] = static::DIR_GENERATED_CODE;
            $writableDirs[] = static::DIR_VIEW_PREPROCESSED;
        } else {
            $writableDirs[] = static::DIR_VAR;
        }

        return array_map(function ($path) use ($relativePath) {
            return $this->getPath($path, $relativePath);
        }, $writableDirs);
    }

    /**
     * @return array
     */
    public function getDefaultConfig(): array
    {
        $config = [
            static::DIR_INIT => [static::PATH => 'init'],
            static::DIR_VAR => [static::PATH => 'var'],
            static::DIR_LOG => [static::PATH => 'var/log'],
            static::DIR_ETC => [static::PATH => 'app/etc'],
            static::DIR_MEDIA => [static::PATH => 'pub/media'],
            static::DIR_VIEW_PREPROCESSED => [static::PATH => 'var/view_preprocessed'],
        ];

        if ($this->magentoVersion->satisfies('2.1.*')) {
            $config[static::DIR_GENERATED_CODE] = [static::PATH => 'var/generation'];
            $config[static::DIR_GENERATED_METADATA] = [static::PATH => 'var/di'];
        } else {
            $config[static::DIR_GENERATED] = [static::PATH => 'generated'];
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
}
