<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\Stage\Deploy\MergedConfig;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator\Deploy\JsonFormatVariable;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class JsonFormatVariableTest extends TestCase
{
    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var MergedConfig|MockObject
     */
    private $mergedConfigMock;

    /**
     * @var Schema|MockObject
     */
    private $schemaMock;

    /**
     * @var JsonFormatVariable
     */
    private $validator;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->mergedConfigMock = $this->createMock(MergedConfig::class);
        $this->schemaMock = $this->createMock(Schema::class);

        $this->validator = new JsonFormatVariable(
            $this->resultFactoryMock,
            $this->mergedConfigMock,
            $this->schemaMock
        );
    }

    public function testValidateSuccess()
    {
        $this->schemaMock->expects($this->once())
            ->method('getVariables')
            ->willReturn([
                DeployInterface::VAR_CLEAN_STATIC_FILES => [
                    Schema::SCHEMA_TYPE => ['boolean'],
                    Schema::SCHEMA_STAGES => [
                        StageConfigInterface::STAGE_GLOBAL,
                        StageConfigInterface::STAGE_DEPLOY
                    ],
                ],
                DeployInterface::VAR_SEARCH_CONFIGURATION => [
                    Schema::SCHEMA_TYPE => ['array'],
                    Schema::SCHEMA_STAGES => [
                        StageConfigInterface::STAGE_GLOBAL,
                        StageConfigInterface::STAGE_DEPLOY
                    ],
                ],
            ]);
        $this->mergedConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                DeployInterface::VAR_CLEAN_STATIC_FILES => true,
                DeployInterface::VAR_SEARCH_CONFIGURATION => ['some_config']
            ]);
        $this->resultFactoryMock->expects($this->once())
            ->method('success');

        $this->validator->validate();
    }

    public function testValidateErrors()
    {
        $this->schemaMock->expects($this->once())
            ->method('getVariables')
            ->willReturn([
                DeployInterface::VAR_CLEAN_STATIC_FILES => [
                    Schema::SCHEMA_TYPE => ['boolean'],
                    Schema::SCHEMA_STAGES => [
                        StageConfigInterface::STAGE_GLOBAL,
                        StageConfigInterface::STAGE_DEPLOY
                    ],
                ],
                DeployInterface::VAR_SEARCH_CONFIGURATION => [
                    Schema::SCHEMA_TYPE => ['array'],
                    Schema::SCHEMA_STAGES => [
                        StageConfigInterface::STAGE_GLOBAL,
                        StageConfigInterface::STAGE_DEPLOY
                    ],
                ],
                DeployInterface::VAR_CACHE_CONFIGURATION => [
                    Schema::SCHEMA_TYPE => ['array'],
                    Schema::SCHEMA_STAGES => [
                        StageConfigInterface::STAGE_GLOBAL,
                        StageConfigInterface::STAGE_DEPLOY
                    ],
                ],
                DeployInterface::VAR_SESSION_CONFIGURATION => [
                    Schema::SCHEMA_TYPE => ['array'],
                    Schema::SCHEMA_STAGES => [
                        StageConfigInterface::STAGE_GLOBAL,
                        StageConfigInterface::STAGE_DEPLOY
                    ],
                ],
                DeployInterface::VAR_CRON_CONSUMERS_RUNNER => [
                    Schema::SCHEMA_TYPE => ['array'],
                    Schema::SCHEMA_STAGES => [
                        StageConfigInterface::STAGE_GLOBAL,
                        StageConfigInterface::STAGE_DEPLOY
                    ],
                ],
            ]);
        $this->mergedConfigMock->expects($this->any())
            ->method('get')
            ->willReturn([
                DeployInterface::VAR_CLEAN_STATIC_FILES => true,
                DeployInterface::VAR_SEARCH_CONFIGURATION => ['some_config'],
                DeployInterface::VAR_CACHE_CONFIGURATION => '{"wrong json",}',
                DeployInterface::VAR_SESSION_CONFIGURATION => '{"save": "redis"}',
                DeployInterface::VAR_CRON_CONSUMERS_RUNNER => '{"wrong json2",}',
            ]);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                'Next variables can\'t be decoded: CACHE_CONFIGURATION (Syntax error), ' .
                'CRON_CONSUMERS_RUNNER (Syntax error)'
            );

        $this->validator->validate();
    }

    public function testValidateErrorsWithException()
    {
        $this->schemaMock->expects($this->once())
            ->method('getVariables')
            ->willReturn([
                DeployInterface::VAR_CRON_CONSUMERS_RUNNER => [
                    Schema::SCHEMA_TYPE => ['array'],
                    Schema::SCHEMA_STAGES => [
                        StageConfigInterface::STAGE_GLOBAL,
                        StageConfigInterface::STAGE_DEPLOY
                    ],
                ],
            ]);
        $this->mergedConfigMock->expects($this->any())
            ->method('get')
            ->willThrowException(new FileSystemException('Read file error'));
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with('Can\'t read merged configuration: Read file error');

        $this->validator->validate();
    }
}
