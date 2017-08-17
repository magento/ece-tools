<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Filesystem\Reader\ReaderInterface;

/**
 * Class Build.
 */
class Build
{
    const BUILD_OPT_SKIP_DI_COMPILATION = 'skip_di_compilation';
    const BUILD_OPT_SKIP_DI_CLEARING = 'skip_di_clearing';
    const BUILD_OPT_SCD_EXCLUDE_THEMES = 'exclude_themes';
    const BUILD_OPT_SCD_THREADS = 'scd_threads';
    const BUILD_OPT_SKIP_SCD = 'skip_scd';

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
     * @param mixed $default
     * @return mixed
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
        return $this->get('VERBOSE_COMMANDS') === 'enabled' ? ' -vv ' : '';
    }
}
