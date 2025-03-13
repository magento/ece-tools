<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Magento\Shared\Reader;
use Magento\MagentoCloud\Config\Validator\Deploy\ElasticSearchIntegrity;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\ValidatorException;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Service\ElasticSearch;
use Magento\MagentoCloud\Service\OpenSearch;
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
     * @var ElasticSearch|MockObject
     */
    private $elasticSearchMock;

    /**
     * @var OpenSearch|MockObject
     */
    private $openSearchMock;

    /**
     * @var Reader|MockObject
     */
    private $readerMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->elasticSearchMock = $this->createMock(ElasticSearch::class);
        $this->openSearchMock = $this->createMock(OpenSearch::class);
        $this->readerMock = $this->createMock(Reader::class);

        $this->validator = new ElasticSearchIntegrity(
            $this->magentoVersionMock,
            $this->resultFactoryMock,
            $this->elasticSearchMock,
            $this->openSearchMock,
            $this->readerMock
        );
    }

    /**
     * @throws ValidatorException
     */
    public function testValidate(): void
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->with('>=2.4.3-p2')
            ->willReturn(false);
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.4.0')
            ->willReturn(false);
        $this->openSearchMock->expects($this->never())
            ->method('isInstalled');
        $this->resultFactoryMock->expects($this->once())
            ->method('success');

        $this->validator->validate();
    }

    /**
     * @throws ValidatorException
     */
    public function testValidateNoElasticSearchAndNoOpenSearchMagentoGreater244(): void
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->with('>=2.4.3-p2')
            ->willReturn(true);
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.4.0')
            ->willReturn(true);
        $this->openSearchMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(false);
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
    public function testValidateWithElasticSearchNoOrWithOpenSearch240(): void
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->with('>=2.4.3-p2')
            ->willReturn(false);
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.4.0')
            ->willReturn(true);
        $this->openSearchMock->expects($this->never())
            ->method('isInstalled');
        $this->elasticSearchMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->resultFactoryMock->expects($this->once())
            ->method('success');

        $this->validator->validate();
    }

    /**
     * @throws ValidatorException
     */
    public function testValidateWithElasticSearchNoOpenSearch244(): void
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->with('>=2.4.3-p2')
            ->willReturn(true);
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->willReturn(true);
        $this->openSearchMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(false);
        $this->elasticSearchMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->resultFactoryMock->expects($this->once())
            ->method('success');

        $this->validator->validate();
    }

    /**
     * @throws ValidatorException
     */
    public function testValidateWithOrNoElasticSearchWithOpenSearch244(): void
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->with('>=2.4.3-p2')
            ->willReturn(true);
        $this->magentoVersionMock->expects($this->never())
            ->method('isGreaterOrEqual');
        $this->openSearchMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->elasticSearchMock->expects($this->never())
            ->method('isInstalled');
        $this->resultFactoryMock->expects($this->once())
            ->method('success');

        $this->validator->validate();
    }

    /**
     * @throws ValidatorException
     */
    public function testValidateNoElasticSearchWithOpenSearch240(): void
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->with('>=2.4.3-p2')
            ->willReturn(false);
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.4.0')
            ->willReturn(true);
        $this->openSearchMock->expects($this->never())
            ->method('isInstalled');
        $this->elasticSearchMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(false);
        $this->resultFactoryMock->expects($this->never())
            ->method('success');
        $this->resultFactoryMock->expects($this->once())
            ->method('errorByCode')
            ->with(Error::DEPLOY_ES_SERVICE_NOT_INSTALLED);

        $this->validator->validate();
    }

    /**
     * @throws ValidatorException
     */
    public function testValidateNoElasticSearchAndNoOpenSearchWithLiveSearchEnabledMagentoGreater244(): void
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->with('>=2.4.3-p2')
            ->willReturn(true);
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.4.0')
            ->willReturn(true);
        $this->openSearchMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(false);
        $this->elasticSearchMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(false);

        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn(['modules' => ['Magento_LiveSearchAdapter' => 1]]);

        $this->resultFactoryMock->expects($this->once())
            ->method('success');

        $this->validator->validate();
    }
}
