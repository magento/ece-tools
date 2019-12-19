<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use CliTester;
use Robo\Exception\TaskException;

/**
 * @inheritDoc
 */
class AbstractInstallCest extends AbstractCest
{
    public const EDITION = 'EE';

    /**
     * @param CliTester $I
     * @throws TaskException
     */
    public function _before(CliTester $I): void
    {
        parent::_before($I);

        $I->cloneTemplate(null, static::EDITION);
        $I->addEceComposerRepo();
    }
}
