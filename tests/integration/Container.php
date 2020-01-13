<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Integration;

use Magento\MagentoCloud\App\ContainerException;

/**
 * Container for Integration Tests
 */
class Container extends \Magento\MagentoCloud\App\Container
{
    /**
     * @var self
     */
    private static $instance;

    /**
     * @param string $toolsBasePath
     * @param string $magentoBasePath
     * @return self
     * @throws ContainerException
     */
    public static function getInstance(string $toolsBasePath, string $magentoBasePath): self
    {
        if (null === self::$instance) {
            self::$instance = new self($toolsBasePath, $magentoBasePath);
        }

        return self::$instance;
    }
}
