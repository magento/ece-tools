<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Illuminate\Contracts\Config\Repository;
use Magento\MagentoCloud\Config\Deploy\Reader;
use Magento\MagentoCloud\Config\Deploy\Writer;

/**
 * Repository for deploy config.
 */
class Deploy implements ConfigInterface
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
     * @var Repository
     */
    private $config;

    /**
     * @var RepositoryFactory
     */
    private $factory;

    public function __construct(Reader $reader, Writer $writer, RepositoryFactory $factory)
    {
        $this->reader = $reader;
        $this->writer = $writer;
        $this->factory = $factory;
    }

    /**
     * @inheritdoc
     */
    public function get(string $key, $default = null)
    {
        return $this->read()->get($key, $default);
    }

    /**
     * @inheritdoc
     */
    public function has(string $key): bool
    {
        return $this->read()->has($key);
    }

    /**
     * @inheritdoc
     */
    public function all(): array
    {
        return $this->read()->all();
    }

    /**
     * @inheritdoc
     */
    public function update(array $config)
    {
        $this->reset();
        $this->writer->update($config);
    }

    /**
     * @inheritdoc
     */
    public function set(string $key, $value)
    {
        $this->read()->set($key, $value);
        $this->writer->update($this->all());
    }

    /**
     * @inheritdoc
     */
    public function reset()
    {
        $this->config = null;
    }

    /**
     * Lod config data from disk (if necessary).
     *
     * @return Repository
     */
    private function read(): Repository
    {
        if ($this->config === null) {
            $this->config = $this->factory->create($this->reader->read());
        }

        return $this->config;
    }
}
