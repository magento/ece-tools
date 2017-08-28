<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem;

class DirectoryList
{
    /**
     * Keys of directory configuration.
     */
    const PATH = 'path';

    const ROOT = 'root';
    const MAGENTO_ROOT = 'magento_root';

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
        if (!array_key_exists($code, $this->directories)) {
            throw  new \RuntimeException("Code {$code} is not registered");
        }

        $root = $this->getRoot();
        $path = $this->directories[$code][self::PATH];
        $normalizedPath = $root . ($root && $path ? DIRECTORY_SEPARATOR : '') . $path;

        return realpath($normalizedPath);
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
        return $this->getPath(static::MAGENTO_ROOT);
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
            static::MAGENTO_ROOT => [static::PATH => '/../../../'],
        ];
    }
}
