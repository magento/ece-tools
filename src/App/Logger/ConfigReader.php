<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App\Logger;

use Magento\MagentoCloud\App\Logger;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Reads and parses handler configurations
 */
class ConfigReader
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param DirectoryList $directoryList
     */
    public function __construct(DirectoryList $directoryList)
    {
        $this->directoryList = $directoryList;
    }

    /**
     * @return array
     * @throws ParseException
     */
    public function getHandlersConfig(): array
    {
        $path = $this->directoryList->getMagentoRoot() . '/' . Logger::CONFIG_HANDLERS_LOG;

        return !file_exists($path) ? [] : (array) Yaml::parse(file_get_contents($path));
    }
}
