<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Filesystem\DirectoryList;

/**
 * Class Logger
 */
class Logger
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
     * @return string
     */
    public function getLineFormat(): string
    {
        return "[%datetime%] %level_name%: %message% %context% %extra%\n";
    }

    /**
     * @return bool
     */
    public function allowInlineLineBreaks(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function ignoreEmptyContextAndExtra() : bool
    {
        return true;
    }

    /**
     * @return null|string
     */
    public function dateFormat()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getDeployLogPath() : string
    {
        return $this->directoryList->getMagentoRoot() . '/var/log/cloud.log';
    }

    /**
     * @return string
     */
    public function getBackupBuildLogPath() : string
    {
        return $this->directoryList->getMagentoRoot() . '/init/var/log/cloud.log';
    }
}
