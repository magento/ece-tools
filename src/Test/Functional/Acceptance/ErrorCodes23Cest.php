<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * This test cover functionality of state-aware error codes.
 * Checks that failed scenario returns correct error code different to 1 or 255.
 * Checks that var/log/cloud.error.log file was created and contains correct data.
 * Checks that `ece-tools error:show` command returns correct errors info
 *
 * @group php73
 */
class ErrorCodes23Cest extends ErrorCodesCest
{
    /**
     * @var boolean
     */
    protected $removeEs = true;

    /**
     * @var string
     */
    protected $magentoCloudTemplate = '2.3.5';

    /**
     * @param string $errorLog
     * @return array
     */
    protected function getErrors(string $errorLog): array
    {
        $errors = [];

        foreach (explode("\n", $errorLog) as $errorLine) {
            $error = json_decode(trim($errorLine), true);
            $errors[$error['errorCode']] = $error;
        }

        return $errors;
    }
}
