<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Validator\Deploy\ElasticSearchIntegrity;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\ValidatorException;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Service\ElasticSearch;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @see ElasticSearchIntegrity
 */
class ElasticsearchIntegrityTest extends TestCase
{
    /**
     * @var ElasticSearchIntegrity
     */
    private $validator;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var ElasticSearch
     */
    private $elasticSearchMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->elasticSearchMock = $this->createMock(ElasticSearch::class);

        $this->validator = new ElasticSearchIntegrity(
            $this->magentoVersionMock,
            $this->resultFactoryMock,
            $this->elasticSearchMock
        );
    }

    /**
     * @throws ValidatorException
     */
    public function testValidate(): void
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.4.0')
            ->willReturn(false);
        $this->resultFactoryMock->expects($this->once())
            ->method('success');

        $this->validator->validate();
    }

    /**
     * @throws ValidatorException
     */
    public function testValidateNoElasticSearch(): void
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.4.0')
            ->willReturn(true);
        $this->elasticSearchMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(false);
        $this->resultFactoryMock->expects($this->once())
            ->method('errorByCode')
            ->with(Error::DEPLOY_ES_SERVICE_NOT_INSTALLED);

        $this->validator->validate();
    }

    /**
     * @throws ValidatorException
     */
    public function testValidateWithElasticSearch(): void
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.4.0')
            ->willReturn(true);
        $this->elasticSearchMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->resultFactoryMock->expects($this->once())
            ->method('success');

        $this->validator->validate();
    }
}
