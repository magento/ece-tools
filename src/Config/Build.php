<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Filesystem\Reader\ReaderInterface;

/**
 * Class Build.
 *
 * @deprecated
 */
class Build
{
    const OPT_SCD_EXCLUDE_THEMES = 'exclude_themes';
    const OPT_SCD_THREADS = 'scd_threads';
    const OPT_VERBOSE_COMMANDS = 'VERBOSE_COMMANDS';
    const OPT_SCD_COMPRESSION_LEVEL = 'SCD_COMPRESSION_LEVEL';

    /**
     * @var ReaderInterface
     */
    private $reader;

    /**
     * @var array
     */
    private $config;

    /**
     * @param ReaderInterface $reader
     */
    public function __construct(ReaderInterface $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @param string $key
     * @param string|null $default
     * @return string|null
     */
    public function get(string $key, $default = null)
    {
        if ($this->config === null) {
            $this->config = $this->reader->read();
        }

        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }

    /**
     * @return string
     */
    public function getVerbosityLevel(): string
    {
        return $this->get(static::OPT_VERBOSE_COMMANDS) === 'enabled' ? ' -vv ' : '';
    }
}
