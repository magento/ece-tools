<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service;

/**
 * Class Php
 */
class Php implements ServiceInterface
{
    /**
     * @var string
     */
    private $version;

    /**
     * Get PHP configuration.
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        return ['version' => PHP_VERSION];
    }

    /**
     * Get the PHP version.
     *
     * @return string
     */
    public function getVersion(): string
    {
        if ($this->version === null) {
            $this->version = '0';

            $phpConfigs = $this->getConfiguration();
            if (isset($phpConfigs['version'])) {
                $this->version = $phpConfigs['version'];
            }
        }

        return $this->version;
    }
}
