<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config;

use \Magento\MagentoCloud\Config\ScdStrategyChecker;
use \Magento\MagentoCloud\App\Logger;
use \Magento\MagentoCloud\Package\MagentoVersion;
use \PHPUnit\Framework\TestCase;
use \PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * Class ScdStrategyCheckerTest
 *
 * @package Magento\MagentoCloud\Test\Unit\Config
 */
class ScdStrategyCheckerTest extends TestCase
{
    /**
     * @var Logger|Mock
     */
    private $loggerMock;

    /**
     * @var MagentoVersion|Mock
     */
    private $magentoVersionMock;

    /**
     * @var ScdStrategyChecker
     */
    private $scdStrategyChecker;

    /**
     *
     */
    protected function setUp()
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->scdStrategyChecker = new ScdStrategyChecker(
            $this->loggerMock,
            $this->magentoVersionMock
        );
    }
}
