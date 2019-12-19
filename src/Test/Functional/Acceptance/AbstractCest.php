<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * General Cest
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
abstract class AbstractCest
{
    /**
     * @param \CliTester $I
     */
    public function _before(\CliTester $I)
    {
        $I->generateDockerCompose();
        $I->cleanUpEnvironment();
    }

    /**
     * @param \CliTester $I
     */
    public function _after(\CliTester $I): void
    {
        $I->stopEnvironment();
        $I->removeDockerCompose();
    }
}
