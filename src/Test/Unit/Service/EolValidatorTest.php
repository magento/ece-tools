<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Service;

use Carbon\Carbon;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Service\ElasticSearch;
use Magento\MagentoCloud\Service\EolValidator;
use Magento\MagentoCloud\Service\ServiceInterface;
use Magento\MagentoCloud\Service\ServiceFactory;
use Magento\MagentoCloud\Service\Validator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class EolValidatorTest extends TestCase
{
    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    /**
     * @var ElasticSearch|MockObject
     */
    private $elasticSearchMock;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var EolValidator
     */
    private $validator;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->serviceFactory = $this->createMock(ServiceFactory::class);
        $this->fileListMock = $this->createMock((FileList::class));
        $this->elasticSearchMock = $this->createMock(ElasticSearch::class);

        $this->validator = new EolValidator(
            $this->fileListMock,
            $this->serviceFactory,
            $this->elasticSearchMock
        );
    }

    /**
     * @throws \Magento\MagentoCloud\Service\ServiceMismatchException
     */
    public function testValidateServiceEol()
    {
        $baseDir = __DIR__ . '/_file/';

        $this->fileListMock->expects($this->any())
            ->method('getServiceEolsConfig')
            ->willReturn($baseDir . '/eol.yaml');

        $this->elasticSearchMock->expects($this->once())
            ->method('getVersion')
            ->willReturn('5.2');

        $service1 = $this->createMock(ServiceInterface::class);
        $service1->expects($this->once())
            ->method('getVersion')
            ->willReturn('3.5');
        $service2 = $this->createMock(ServiceInterface::class);
        $service2->expects($this->once())
            ->method('getVersion')
            ->willReturn('3.2');
        $service3 = $this->createMock(ServiceInterface::class);
        $service3->expects($this->once())
            ->method('getVersion')
            ->willReturn('10.2');
        $this->serviceFactory->expects($this->exactly(3))
            ->method('create')
            ->willReturnOnConsecutiveCalls($service1, $service2, $service3);

        $this->assertEquals(5, count($this->validator->validateServiceEol(ValidatorInterface::LEVEL_WARNING)));
    }

    /**
     * Test compatible service/version.
     */
    public function testCompatibleVersion()
    {
        $baseDir = __DIR__ . '/_file/';

        $this->fileListMock->expects($this->any())
            ->method('getServiceEolsConfig')
            ->willReturn($baseDir . '/eol_2.yaml');

        $serviceName = ServiceInterface::NAME_ELASTICSEARCH;
        $serviceVersion = '6.5';

        $this->assertEquals(
            '',
            $this->validator->validateService($serviceName, $serviceVersion, ValidatorInterface::LEVEL_NOTICE)
        );
    }

    /**
     * Test service/version approaching EOL.
     */
    public function testValidateNoticeMessage()
    {
        $baseDir = __DIR__ . '/_file/';

        $this->fileListMock->expects($this->any())
            ->method('getServiceEolsConfig')
            ->willReturn($baseDir . '/eol_2.yaml');

        $serviceName = ServiceInterface::NAME_PHP;
        $serviceVersion = '7.1.30';
        $eolDate = Carbon::create(2019, 12, 1);
        $message = sprintf(
            '%s %s is approaching EOL (%s).',
            $serviceName,
            $serviceVersion,
            date_format($eolDate, 'Y-m-d')
        );

        $this->assertEquals(
            $message,
            $this->validator->validateService($serviceName, $serviceVersion, ValidatorInterface::LEVEL_NOTICE)
        );
    }

    /**
     * Test service/version passed EOL.
     */
    public function testValidateWarningMessage()
    {
        $baseDir = __DIR__ . '/_file/';

        $this->fileListMock->expects($this->any())
            ->method('getServiceEolsConfig')
            ->willReturn($baseDir . '/eol_2.yaml');

        $serviceName = ServiceInterface::NAME_PHP;
        $serviceVersion = '7.0';
        $eolDate = Carbon::create(2018, 12, 1);
        $message = sprintf(
            '%s %s has passed EOL (%s).',
            $serviceName,
            $serviceVersion,
            date_format($eolDate, 'Y-m-d')
        );

        $this->assertEquals(
            $message,
            $this->validator->validateService($serviceName, $serviceVersion, ValidatorInterface::LEVEL_WARNING)
        );
    }
}
