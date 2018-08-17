<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Magento;

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
     * @param ShellInterface $shell
     */
    public function __construct(ShellInterface $shell)
    {
        $this->shell = $shell;
    }

    /**
     * Read a value from bin/magento config:show and compare it to an expected value.
     *
     * @param string $key
     * @return string|null
     */
    public function get(string $key)
    {
        try {
            $result = implode(PHP_EOL, $this->shell->execute(sprintf(
                './bin/magento config:show %s',
                escapeshellarg($key)
            )));
        } catch (\Exception $e) {
            return null;
        }

        return $result;
    }
}
