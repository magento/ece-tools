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
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\Manager;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine\ElasticSearch;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine\Config as SearchEngineConfig;
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
     * @var SearchEngineConfig|MockObject
     */
    private $searchEngineConfigMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->managerMock = $this->createMock(Manager::class);
        $this->elasticSearchMock = $this->createMock(ElasticSearch::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->searchEngineConfigMock = $this->createMock(SearchEngineConfig::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->validator = new ElasticSearchVersion(
            $this->resultFactoryMock,
            $this->managerMock,
            $this->elasticSearchMock,
            $this->loggerMock,
            $this->searchEngineConfigMock,
            $this->magentoVersionMock
        );
    }

    public function testValidateElasticSearchServiceNotExists()
    {
        $this->elasticSearchMock->expects($this->once())
            ->method('getVersion')
            ->willReturn('0');
        $this->managerMock->expects($this->never())
            ->method('get');
        $this->searchEngineConfigMock->expects($this->never())
            ->method('get');
        $this->loggerMock->expects($this->never())
            ->method('warning');
        $this->resultFactoryMock->expects($this->once())
            ->method('success');

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    public function testValidatePackageNotExists()
    {
        $this->searchEngineConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(['engine' => 'elasticsearch5']);
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
        $this->resultFactoryMock->expects($this->once())
            ->method('success');

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    public function testValidateElasticSearchServiceExistsAndNotConfigured()
    {
        $this->searchEngineConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(['engine' => 'mysql']);
        $this->elasticSearchMock->expects($this->once())
            ->method('getVersion')
            ->willReturn(2);
        $this->managerMock->expects($this->never())
            ->method('get');
        $this->loggerMock->expects($this->never())
            ->method('warning');
        $this->resultFactoryMock->expects($this->once())
            ->method('success');

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    /**
     * @param string $esVersion
     * @param string $packageVersion
     * @param string $expectedResultClass
     * @param string $magentoVersion
     * @param string $errorMessage
     * @param string|null $errorSuggestion
     * @dataProvider validateDataProvider
     */
    public function testValidate(
        string $esVersion,
        string $packageVersion,
        string $expectedResultClass,
        string $magentoVersion = '2.2',
        string $errorMessage = '',
        string $errorSuggestion = ''
    ) {
        $this->magentoVersionMock->expects($this->any())
                ->method('getVersion')
                ->willReturn($magentoVersion);
        $this->searchEngineConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(['engine' => 'elasticsearch']);
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

        if ($errorMessage) {
            $this->resultFactoryMock->expects($this->once())
                ->method('error')
                ->with($errorMessage, $errorSuggestion);
        }

        $this->assertInstanceOf($expectedResultClass, $this->validator->validate());
    }

    /**
     * @return array
     */
    public function validateDataProvider(): array
    {
        return [
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
            ['6.1', '2.0', Error::class],
            [
                '6.2',
                '5.0',
                Error::class,
                '2.3.0',
                'Elasticsearch service version 6.2 on infrastructure layer is not compatible with current version of ' .
                'elasticsearch/elasticsearch module (5.0), used by your Magento application.',
                'You can fix this issue by upgrading the Elasticsearch service on your ' .
                'Magento Cloud infrastructure to version 5.x.'
            ],
            [
                '5.0',
                '6.0',
                Error::class,
                '2.3.4',
                'Elasticsearch service version 5.0 on infrastructure layer is not compatible with current version of ' .
                'elasticsearch/elasticsearch module (6.0), used by your Magento application.',
                'You can fix this issue by upgrading the Elasticsearch service on your ' .
                'Magento Cloud infrastructure to version 6.x.'
            ],
            ['5.0', '2.9', Error::class],
            [
                '5.0',
                '2.0',
                Error::class,
                '2.1.4',
                'Elasticsearch service version 5.0 on infrastructure layer is not compatible with current version of ' .
                'elasticsearch/elasticsearch module (2.0), used by your Magento application.',
                'You can fix this issue by upgrading the Elasticsearch service on your ' .
                'Magento Cloud infrastructure to version 1.x or 2.x.'
            ],
            [
                '2.0',
                '5.1',
                Error::class,
                '2.2.2',
                'Elasticsearch service version 2.0 on infrastructure layer is not compatible with current version of ' .
                'elasticsearch/elasticsearch module (5.1), used by your Magento application.',
                'You can fix this issue by upgrading the Elasticsearch service on your ' .
                'Magento Cloud infrastructure to version 5.x.'
            ],
            [
                '2.0',
                '5.1',
                Error::class,
                '2.2.3',
                'Elasticsearch service version 2.0 on infrastructure layer is not compatible with current version of ' .
                'elasticsearch/elasticsearch module (5.1), used by your Magento application.',
                'Use one of the following methods to fix this issue:' . PHP_EOL .
                '  Upgrade the Elasticsearch service on your Magento Cloud infrastructure to version 5.x (preferred).' .
                PHP_EOL .
                '  Update the composer.json file for your Magento Cloud project to ' .
                'require elasticsearch/elasticsearch module version ~2.0.'
            ],
            [
                '1.7',
                '5.0',
                Error::class,
                '2.2.9',
                'Elasticsearch service version 1.7 on infrastructure layer is not compatible with current version of ' .
                'elasticsearch/elasticsearch module (5.0), used by your Magento application.',
                'Use one of the following methods to fix this issue:' . PHP_EOL .
                '  Upgrade the Elasticsearch service on your Magento Cloud infrastructure to version 5.x (preferred).' .
                PHP_EOL .
                '  Update the composer.json file for your Magento Cloud project to ' .
                'require elasticsearch/elasticsearch module version ~2.0.'
            ],
            [
                '1.7',
                '5.1',
                Error::class,
                '2.3.0',
                'Elasticsearch service version 1.7 on infrastructure layer is not compatible with current version of ' .
                'elasticsearch/elasticsearch module (5.1), used by your Magento application.',
                'You can fix this issue by upgrading the Elasticsearch service on your ' .
                'Magento Cloud infrastructure to version 5.x.'
            ],
        ];
    }
}
