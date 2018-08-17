<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Magento;

use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Shell\ShellInterface;

/**
 * Retrieves a value by running bin/magento config:show
 */
class System
{
    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param ShellInterface $shell
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(ShellInterface $shell, MagentoVersion $magentoVersion)
    {
        $this->shell = $shell;
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * Read a value from bin/magento config:show and compare it to an expected value.
     *
     * @param string $key
     * @return string|null
     *
     * @throws UndefinedPackageException
     */
    public function get(string $key)
    {
        if (!$this->magentoVersion->isGreaterOrEqual('2.2.0')) {
            return null;
        }

        try {
            $result = implode(PHP_EOL, $this->shell->execute(sprintf(
                'php ./bin/magento config:show %s',
                escapeshellarg($key)
            )));
        } catch (\Exception $e) {
            return null;
        }

        return $result;
    }
}
