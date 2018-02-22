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
     * @param bool $expectedResult
     * @param string $branchName
     * @dataProvider isMasterBranchDataProvider
     */
    public function testIsMasterBranch(bool $expectedResult, string $branchName)
    {
        $_ENV['MAGENTO_CLOUD_ENVIRONMENT'] = $branchName;

        $this->assertSame(
            $expectedResult,
            $this->environment->isMasterBranch()
        );
    }

    /**
     * @return array
     */
    public function isMasterBranchDataProvider(): array
    {
        return [
            [false, 'branch213'],
            [false, 'prod-branch'],
            [false, 'stage'],
            [false, 'branch-production-lad13m'],
            [false, 'branch-staging-lad13m'],
            [false, 'branch-master-lad13m'],
            [false, 'branch-production'],
            [false, 'branch-staging'],
            [false, 'branch-master'],
            [false, 'product'],
            [true, 'staging'],
            [true, 'staging-ba3ma'],
            [true, 'master'],
            [true, 'master-ad123m'],
            [true, 'production'],
            [true, 'production-lad13m'],
        ];
    }
}
