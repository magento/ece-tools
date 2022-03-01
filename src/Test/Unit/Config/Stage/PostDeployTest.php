<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Stage;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\Stage\PostDeploy;
use Magento\MagentoCloud\Config\Stage\PostDeployInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * @inheritdoc
 */
class PostDeployTest extends TestCase
{
    /**
     * @var PostDeploy
     */
    private $config;

    /**
     * @var EnvironmentReader|MockObject
     */
    private $environmentReaderMock;

    /**
     * @var Schema|MockObject
     */
    private $schemaMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->environmentReaderMock = $this->createMock(EnvironmentReader::class);
        $this->schemaMock = $this->createMock(Schema::class);
        $this->schemaMock->method('getDefaults')
            ->with(StageConfigInterface::STAGE_POST_DEPLOY)
            ->willReturn([
                PostDeployInterface::VAR_WARM_UP_PAGES => ['index.php']
            ]);

        $this->config = new PostDeploy(
            $this->environmentReaderMock,
            $this->schemaMock
        );
    }

    /**
     * @param string $name
     * @param array $envConfig
     * @param mixed $expectedValue
     * @dataProvider getDataProvider
     *
     * @throws ConfigException
     */
    public function testGet(string $name, array $envConfig, $expectedValue): void
    {
        $this->environmentReaderMock->method('read')
            ->willReturn([PostDeploy::SECTION_STAGE => $envConfig]);

        $this->assertSame($expectedValue, $this->config->get($name));
    }

    /**
     * @return array
     */
    public function getDataProvider(): array
    {
        return [
            'default pages' => [
                PostDeploy::VAR_WARM_UP_PAGES,
                [],
                [
                    'index.php',
                ],
            ],
            'custom pages' => [
                PostDeploy::VAR_WARM_UP_PAGES,
                [
                    PostDeploy::STAGE_POST_DEPLOY => [
                        PostDeploy::VAR_WARM_UP_PAGES => [
                            'index.php/custom',
                        ],
                    ],
                ],
                [
                    'index.php/custom',
                ],
            ],
        ];
    }

    /**
     * @throws ConfigException
     */
    public function testNotExists(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Config NOT_EXISTS_VALUE was not defined.');
        $this->expectExceptionCode(Error::PD_CONFIG_NOT_DEFINED);

        $this->environmentReaderMock->expects($this->never())
            ->method('read')
            ->willReturn([]);

        $this->config->get('NOT_EXISTS_VALUE');
    }

    /**
     * @throws ConfigException
     */
    public function testUnableToReadMagentoEnvYAml(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Some error');
        $this->expectExceptionCode(Error::PD_CONFIG_UNABLE_TO_READ);

        $this->environmentReaderMock->expects($this->once())
            ->method('read')
            ->willThrowException(new FileSystemException('Some error'));

        $this->config->get(PostDeploy::VAR_WARM_UP_PAGES);
    }

    /**
     * @throws ConfigException
     */
    public function testUnableToParseMagentoEnvYaml(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Some error');
        $this->expectExceptionCode(Error::PD_CONFIG_PARSE_FAILED);

        $this->environmentReaderMock->expects($this->once())
            ->method('read')
            ->willThrowException(new ParseException('Some error'));

        $this->config->get(PostDeploy::VAR_WARM_UP_PAGES);
    }

    /**
     * @throws ConfigException
     */
    public function testUnableToReadSchemaFile(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Some error');
        $this->expectExceptionCode(Error::PD_CONFIG_UNABLE_TO_READ_SCHEMA_YAML);

        $this->schemaMock->expects($this->once())
            ->method('getDefaults')
            ->willThrowException(new FileSystemException('Some error'));

        $this->config->get(PostDeploy::VAR_WARM_UP_PAGES);
    }
}
