<?php
declare(strict_types=1);

namespace Magento\MagentoCloud\Cache;

use Magento\MagentoCloud\Service\ServiceInterface;

/**
 * Abstract class for caching services.
 */
abstract class AbstractService implements ServiceInterface
{
    /**
     * @var array
     */
    protected $configuration;

    /**
     * AbstractService constructor.
     *
     * @param array $configuration
     */
    public function __construct(array $configuration = [])
    {
        $this->configuration = $configuration;
    }

    /**
     * Get the service configuration.
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * Get the service version.
     *
     * @return string
     */
    abstract public function getVersion(): string;
}
