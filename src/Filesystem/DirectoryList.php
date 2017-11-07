<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem;

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
    const ROOT = 'root';
    const MAGENTO_ROOT = 'magento_root';
    const INIT = 'init';
    const VAR = 'var';
    const LOG = 'log';

    /**
     * @var string
     */
    private $root;

    /**
     * @var array
     */
    private $directories;

    /**
     * @param string $root
     * @param array $config
     */
    public function __construct(string $root, array $config = [])
    {
        $this->root = $root;
        $this->directories = $config + static::getDefaultConfig();
    }

    /**
     * Gets a filesystem path of a directory
     *
     * @param string $code
     * @return string
     */
    public function getPath(string $code): string
    {
        $root = $this->getRoot();
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
        $normalizedPath = $root . ($root && $path ? '/' : '') . $path;

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
        return $this->getPath(static::MAGENTO_ROOT);
    }

    /**
     * @return string
     */
    public function getInit(): string
    {
        return $this->getPath(static::INIT);
    }

    /**
     * @return string
     */
    public function getVar(): string
    {
        return $this->getPath(static::VAR);
    }

    /**
     * @return string
     */
    public function getLog(): string
    {
        return $this->getPath(static::LOG);
    }

    /**
     * @return array
     */
    public static function getDefaultConfig(): array
    {
        return [
            static::ROOT => [static::PATH => ''],
            /*
             * Magento application's vendor folder.
             */
            static::MAGENTO_ROOT => [static::PATH => '../../..'],
            static::INIT => [static::PATH => '../../../init'],
            static::VAR => [static::PATH => '../../../var'],
            static::LOG => [static::PATH => '../../../var/log'],
        ];
    }
}
