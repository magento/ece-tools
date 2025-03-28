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
        return $this->getConfiguration()['version'];
    }

    /**
     * Checks if opcache is enabled for PHP CLI
     *
     * @return bool
     */
    public function isOpcacheCliEnabled(): bool
    {
        return (bool)ini_get('opcache.enable_cli');
    }

    /**
     * Resets the contents of the opcache
     *
     * @return bool
     */
    public function resetOpcache(): bool
    {
        return opcache_reset();
    }
}
