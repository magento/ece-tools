<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service\Valkey;

use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Service\ServiceException;
use Magento\MagentoCloud\Shell\ShellException;

/**
 * Returns Valkey version
 */
class Version
{
    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @param ShellInterface $shell
     */
    public function __construct(ShellInterface $shell)
    {
        $this->shell = $shell;
    }

    /**
     * Retrieves Valkey service version whether from relationship configuration
     * or using CLI command (for PRO environments)
     *
     * @param array $valkeyConfig
     * @return string
     * @throws ServiceException
     */
    public function getVersion(array $valkeyConfig): string
    {
        $version = '0';

        // On integration environments
        if (isset($valkeyConfig['type']) && strpos($valkeyConfig['type'], ':') !== false) {
            $version = explode(':', $valkeyConfig['type'])[1];
        } elseif (isset($valkeyConfig['host'], $valkeyConfig['port'])) {
            // On dedicated environments
            $cmd = sprintf('valkey-cli -p %s -h %s', (string)$valkeyConfig['port'], (string)$valkeyConfig['host']);

            if (!empty($valkeyConfig['password'])) {
                $cmd .= ' -a ' . $valkeyConfig['password'];
            }

            try {
                $process = $this->shell->execute($cmd .' info | grep valkey_version');

                preg_match('/^(?:valkey_version:)(\d+\.\d+)/', $process->getOutput(), $matches);
                $version = $matches[1] ?? '0';
            } catch (ShellException $exception) {
                throw new ServiceException($exception->getMessage());
            }
        }

        return $version;
    }
}
