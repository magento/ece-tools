<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Cron;

use Magento\MagentoCloud\Config\Deploy\Reader;
use Magento\MagentoCloud\Config\Deploy\Writer;
use Magento\MagentoCloud\Filesystem\FileSystemException;

/**
 * Enables/disables Magento crons
 */
class Switcher
{
    /**
     * Deploy Config Writer
     *
     * @var Writer
     */
    private $writer;

    /**
     * Deploy Config Reader
     *
     * @var Reader
     */
    private $reader;

    /**
     * @param Writer $writer Deploy Config Writer
     * @param Reader $reader Deploy Config Reader
     */
    public function __construct(
        Writer $writer,
        Reader $reader
    ) {
        $this->writer = $writer;
        $this->reader = $reader;
    }

    /**
     * Removes cron enabled flag from Magento configuration file.
     *
     * @throws FileSystemException
     */
    public function enable(): void
    {
        $config = $this->reader->read();
        unset($config['cron']['enabled']);
        $this->writer->create($config);
    }

    /**
     * Add cron enabled flag to Magento configuration file.
     *
     * @throws FileSystemException
     */
    public function disable(): void
    {
        $this->writer->update(['cron' => ['enabled' => 0]]);
    }
}
