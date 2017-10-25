<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Config\Shared\Reader;

/**
 * Class Shared.
 */
class Shared
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var array
     */
    private $config;

    /**
     * @param Reader $reader
     */
    public function __construct(
        Reader $reader
    ) {
        $this->reader = $reader;
    }

    /**
     * @param string $key
     * @param string|null $default
     * @return mixed|null
     */
    public function get(string $key, $default = null)
    {
        if ($this->config === null) {
            $this->config = $this->reader->read();
        }
        return $this->config[$key] ?? $default;
    }
}
