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
     * Magento env config writer
     *
     * @var WriterInterface
     */
    private $writer;

    /**
     * Magento env config reader
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
     * Unsets flag from Magento configuration file to enable cron running
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
     * Disable cron running by adding appropriate value to cron enable flag
     *
     * @throws FileSystemException
     */
    public function disable(): void
    {
        $this->writer->update(['cron' => ['enabled' => 0]]);
    }
}
