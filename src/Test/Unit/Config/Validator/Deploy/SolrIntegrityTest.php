<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Validator\Deploy\SolrIntegrity;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class SolrIntegrityTest extends TestCase
{
    /**
     * @var SolrIntegrity
     */
    private $validator;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->validator = new SolrIntegrity(
            $this->environmentMock,
            $this->magentoVersionMock,
            $this->resultFactoryMock
        );
    }

    /**
     * @throws UndefinedPackageException
     */
    public function testConfigValid()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn([
                'elasticsearch' => [['host' => '127.0.0.1']],
                'database' => [
                    [
                        'host' => 'database.internal',
                        'scheme' => 'mysql',
                    ],
                ],
            ]);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultInterface::SUCCESS)
            ->willReturn($this->createMock(Success::class));

        $this->validator->validate();
    }

    /**
     * @throws UndefinedPackageException
     */
    public function testConfigSolr21()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn([
                'solr' => [['host' => '127.0.0.1']],
                'database' => [
                    [
                        'host' => 'database.internal',
                        'scheme' => 'mysql',
                    ],
                ],
            ]);
        $this->magentoVersionMock->method('satisfies')
            ->willReturnMap([
                ['2.1.*', true],
                ['>=2.2', false],
            ]);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                ResultInterface::ERROR,
                [
                    'error' => 'Configuration for Solr was found in .magento.app.yaml.',
                    'suggestion' => 'Solr support has been deprecated in Magento 2.1. ' .
                        'Remove this relationship and use Elasticsearch.',
                    'errorCode' => \Magento\MagentoCloud\App\Error::WARN_SOLR_DEPRECATED
                ]
            )->willReturn($this->createMock(Error::class));

        $this->validator->validate();
    }

    /**
     * @throws UndefinedPackageException
     */
    public function testConfigSolr22()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn([
                'solr' => [['host' => '127.0.0.1']],
                'database' => [
                    [
                        'host' => 'database.internal',
                        'scheme' => 'mysql',
                    ],
                ],
            ]);

        $this->magentoVersionMock->method('satisfies')
            ->willReturnMap([
                ['2.1.*', false],
                ['>=2.2', true],
            ]);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                ResultInterface::ERROR,
                [
                    'error' => 'Configuration for Solr was found in .magento.app.yaml.',
                    'suggestion' => 'Solr is no longer supported by Magento 2.2 or later. ' .
                        'Remove this relationship and use Elasticsearch.',
                    'errorCode' => \Magento\MagentoCloud\App\Error::WARN_SOLR_NOT_SUPPORTED
                ]
            )->willReturn($this->createMock(Error::class));

        $this->validator->validate();
    }
}
