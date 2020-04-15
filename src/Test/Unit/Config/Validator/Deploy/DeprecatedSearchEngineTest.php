<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\SearchEngine;
use Magento\MagentoCloud\Config\Validator\Deploy\DeprecatedSearchEngine;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\ValidatorException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @see DeprecatedSearchEngine
 */
class DeprecatedSearchEngineTest extends TestCase
{
    /**
     * @var DeprecatedSearchEngine
     */
    private $validator;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var SearchEngine|MockObject
     */
    private $searchEngineMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->searchEngineMock = $this->createMock(SearchEngine::class);

        $this->validator = new DeprecatedSearchEngine(
            $this->resultFactoryMock,
            $this->searchEngineMock
        );
    }

    /**
     * @throws ValidatorException
     */
    public function testValidate(): void
    {
        $this->searchEngineMock->method('getName')
            ->willReturn(SearchEngine::ENGINE_MYSQL);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                'The MySQL search configuration option is deprecated. Use Elasticsearch instead.'
            )->willReturn(new Error('Some error'));

        $this->validator->validate();
    }

    /**
     * @throws ValidatorException
     */
    public function testValidateWithError(): void
    {
        $this->searchEngineMock->method('getName')
            ->willReturn('es');
        $this->resultFactoryMock->expects($this->once())
            ->method('success')
            ->willReturn(new Success());

        $this->validator->validate();
    }
}
