<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit;

use Magento\MagentoCloud\Environment;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class EnvironmentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Environment
     */
    private $model;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['notice', 'pushHandler'])
            ->getMockForAbstractClass();

        $this->model = new Environment(
            $this->loggerMock
        );
    }

    public function testLog()
    {
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Some message');

        $this->model->log('Some message');
    }
}
