<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Composer\Package\PackageInterface;
use Magento\MagentoCloud\Config\Validator\Deploy\ElasticSearchVersion;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Package\Manager;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine\ElasticSearch;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ElasticSearchVersionTest extends TestCase
{
    /**
     * @var ElasticSearchVersion
     */
    private $validator;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var Manager|MockObject
     */
    private $managerMock;

    /**
     * @var ElasticSearch|MockObject
     */
    private $elasticSearchMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->resultFactoryMock = $this->createConfiguredMock(ResultFactory::class, [
            'success' => $this->createMock(Success::class),
            'error' => $this->createMock(Error::class)
        ]);
        $this->managerMock = $this->createMock(Manager::class);
        $this->elasticSearchMock = $this->createMock(ElasticSearch::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->validator = new ElasticSearchVersion(
            $this->resultFactoryMock,
            $this->managerMock,
            $this->elasticSearchMock,
            $this->loggerMock
        );
    }

    public function testValidateElasticSearchNotExists()
    {
        $this->elasticSearchMock->expects($this->once())
            ->method('getVersion')
            ->willReturn('0');
        $this->managerMock->expects($this->never())
            ->method('get');
        $this->loggerMock->expects($this->never())
            ->method('warning');

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    public function testValidatePackageNotExists()
    {
        $this->elasticSearchMock->expects($this->once())
            ->method('getVersion')
            ->willReturn(2);
        $this->managerMock->expects($this->once())
            ->method('get')
            ->with('elasticsearch/elasticsearch')
            ->willThrowException(new \Exception('package doesn\'t exist'));
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Can\'t validate version of elasticsearch: package doesn\'t exist');

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    /**
     * @param string $esVersion
     * @param string $packageVersion
     * @param string $expectedResultClass
     * @dataProvider validateDataProvider
     */
    public function testValidate(string $esVersion, string $packageVersion, string $expectedResultClass)
    {
        $this->elasticSearchMock->expects($this->once())
            ->method('getVersion')
            ->willReturn($esVersion);
        $packageMock = $this->getMockForAbstractClass(PackageInterface::class);
        $packageMock->expects($this->once())
            ->method('getVersion')
            ->willReturn($packageVersion);
        $this->managerMock->expects($this->once())
            ->method('get')
            ->with('elasticsearch/elasticsearch')
            ->willReturn($packageMock);
        $this->loggerMock->expects($this->never())
            ->method('warning');

        $this->assertInstanceOf($expectedResultClass, $this->validator->validate());
    }

    /**
     * @return array
     */
    public function validateDataProvider(): array
    {
        return [
            ['6.1', '2.0', Error::class],
            ['6.2', '5.0', Error::class],
            ['6.0', '6.0', Success::class],
            ['6.1', '6.5', Success::class],
            ['6.0', '6.1', Success::class],
            ['2.9', '2.0', Success::class],
            ['2.5', '2.0', Success::class],
            ['2.3', '2.1', Success::class],
            ['2.2', '2.9', Success::class],
            ['1.7', '2.0', Success::class],
            ['1.7', '2.1', Success::class],
            ['1.7', '2.9', Success::class],
            ['5.1', '5.0', Success::class],
            ['5.2', '5.1', Success::class],
            ['5.0', '2.9', Error::class],
            ['5.0', '2.0', Error::class],
            ['2.0', '5.1', Error::class],
            ['1.7', '5.1', Error::class],
            ['1.7', '5.0', Error::class],
        ];
    }
}
