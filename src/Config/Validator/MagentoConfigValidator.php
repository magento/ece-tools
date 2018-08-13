<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator;

use Magento\MagentoCloud\Shell\ExecBinMagento;

/**
 * Validate a value by running bin/magento config:show
 */
class MagentoConfigValidator
{
    /**
     * @var ExecBinMagento
     */
    private $shell;

    /**
     * @param ExecBinMagento $shell
     */
    public function __construct(ExecBinMagento $shell)
    {
        $this->shell = $shell;
    }

    /**
     * Read a value from bin/magento config:show and compare it to an expected value.
     *
     * @param string $key
     * @param mixed $expectedValue
     * @param mixed $defaultValue
     * @return bool
     */
    public function validate(string $key, $expectedValue, $defaultValue = null): bool
    {
        try {
            $result = implode(PHP_EOL, array_map('trim', $this->shell->execute('config:show', $key)));
        } catch (\RunTimeException $e) {
            $result = $defaultValue;
        }

        return $result == $expectedValue;
    }
}
