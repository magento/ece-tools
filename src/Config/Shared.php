<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Config\Shared\Reader;
use Magento\MagentoCloud\Config\Shared\Writer;

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
     * @var Writer
     */
    private $writer;

    /**
     * @var array
     */
    private $config;

    /**
     * @param Reader $reader
     * @param Writer $writer
     */
    public function __construct(
        Reader $reader,
        Writer $writer
    ) {
        $this->reader = $reader;
        $this->writer = $writer;
    }

    /**
     * @param string $key
     * @param string|null $default
     * @return mixed|null
     */
    public function get(string $key, $default = null)
    {
        return $this->read()[$key] ?? $default;
    }

    /**
     * @return array
     */
    public function read(): array
    {
        if ($this->config === null) {
            $this->config = $this->reader->read();
        }

        return $this->config;
    }

    /**
     * @param array $config
     */
    public function update(array $config)
    {
        $this->reset();
        $this->writer->update($config);
    }

    /**
     * Resets cached data.
     */
    public function reset()
    {
        $this->config = null;
    }
}
