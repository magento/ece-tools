<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Illuminate\Contracts\Config\Repository;
use Magento\MagentoCloud\Config\Shared\Reader;
use Magento\MagentoCloud\Config\Shared\Writer;

/**
 * Class Shared.
 */
class Shared implements ConfigInterface
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
     * @var Repository|null
     */
    private $config;

    /**
     * @var RepositoryFactory
     */
    private $repositoryFactory;

    /**
     * @param Reader $reader
     * @param Writer $writer
     * @param RepositoryFactory $repositoryFactory
     */
    public function __construct(
        Reader $reader,
        Writer $writer,
        RepositoryFactory $repositoryFactory
    ) {
        $this->reader = $reader;
        $this->writer = $writer;
        $this->repositoryFactory = $repositoryFactory;
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
    public function get(string $key)
    {
        return $this->read()->get($key) ?? null;
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
    public function reset()
    {
        $this->config = null;
    }

    /**
     * @return Repository
     */
    private function read(): Repository
    {
        if ($this->config === null) {
            $this->config = $this->repositoryFactory->create(
                $this->reader->read()
            );
        }

        return $this->config;
    }
}
