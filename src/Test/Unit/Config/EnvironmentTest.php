<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class EnvironmentTest extends TestCase
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var FlagManager|Mock
     */
    private $flagManagerMock;

    /**
     * @var array
     */
    private $environmentData;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentData = $_ENV;
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);

        $this->environment = new Environment(
            $this->loggerMock,
            $this->fileMock,
            $this->directoryListMock,
            $this->flagManagerMock
        );
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        $_ENV = $this->environmentData;
    }

    /**
     * @param array $env
     * @param string $key
     * @param mixed $default
     * @param mixed $expected
     * @dataProvider getDataProvider
     */
    public function testGet(array $env, string $key, $default, $expected)
    {
        $_ENV = $env;

        $this->assertSame($expected, $this->environment->get($key, $default));
    }

    /**
     * @return array
     */
    public function getDataProvider(): array
    {
        return [
            'string value' => [
                ['some_key' => base64_encode(json_encode('some_value'))],
                'some_key',
                null,
                'some_value',
            ],
            'empty value' => [
                [],
                'some_key',
                null,
                null,
            ],
            'empty value with default' => [
                [],
                'some_key',
                'some_new_value',
                'some_new_value',
            ],
        ];
    }

    public function testGetWritableDirectories()
    {
        $this->assertSame(
            ['var', 'app/etc', 'pub/media'],
            $this->environment->getWritableDirectories()
        );
    }

    /**
     * @param bool $isExists
     * @dataProvider isDeployStaticContentToBeEnabledDataProvider
     */
    public function testIsDeployStaticContentToBeEnabled(bool $isExists)
    {
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn($isExists);

        $this->assertSame(
            !$isExists,
            $this->environment->isDeployStaticContent()
        );
    }

    /**
     * @return array
     */
    public function isDeployStaticContentToBeEnabledDataProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }

    public function testGetDefaultCurrency()
    {
        $this->assertSame(
            'USD',
            $this->environment->getDefaultCurrency()
        );
    }

    /**
     * @return array
     */
    public function getCronConsumersRunnerDataProvider(): array
    {
        return [
            ['variables' => [], 'expectedResult' => []],
            [
                'variables' => [
                    'CRON_CONSUMERS_RUNNER' => [
                        'cron_run' => 'false',
                        'max_messages' => '100',
                        'consumers' => ['test'],
                    ],
                ],
                'expectedResult' => [
                    'cron_run' => 'false',
                    'max_messages' => '100',
                    'consumers' => ['test'],
                ],
            ],
            [
                'variables' => [
                    'CRON_CONSUMERS_RUNNER' => '{"cron_run":"false", "max_messages":"100", "consumers":["test"]}',
                ],
                'expectedResult' => [
                    'cron_run' => 'false',
                    'max_messages' => '100',
                    'consumers' => ['test'],
                ],
            ],
        ];
    }
}
