<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Cron;

use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface;
use Magento\MagentoCloud\Filesystem\FileSystemException;

/**
 * Enables/disables Magento crons
 */
class Switcher
{
    /**
     * Deploy Config Writer
     *
     * @var WriterInterface
     */
    private $writer;

    /**
     * Deploy Config Reader
     *
     * @var ReaderInterface
     */
    private $reader;

    /**
     * @param WriterInterface $writer
     * @param ReaderInterface $reader
     */
    public function __construct(
        WriterInterface $writer,
        ReaderInterface $reader
    ) {
        $this->writer = $writer;
        $this->reader = $reader;
    }

    /**
     * Removes cron enabled flag from Magento configuration file
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
     * Add cron enabled flag to Magento configuration file
     *
     * @throws FileSystemException
     */
    public function disable(): void
    {
        $this->writer->update(['cron' => ['enabled' => 0]]);
    }
}
