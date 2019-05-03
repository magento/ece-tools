<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\PlatformVariable\Decoder;
use Magento\MagentoCloud\Config\Environment\Reader;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\SystemConfigInterface;
use Magento\MagentoCloud\Config\System\Variables;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class EnvironmentTest extends TestCase
{
    use PHPMock;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var Variables
     */
    private $variable;

    /**
     * @var boolean
     */
    protected $backupGlobals = true;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        /** @var MockObject|Reader $environmentReaderMock */
        /** @var MockObject|Schema $schemaMock */
        $environmentReaderMock = $this->createMock(Reader::class);
        $schemaMock = $this->createMock(Schema::class);
        $schemaMock->expects($this->any())
            ->method('getDefaults')
            ->with(SystemConfigInterface::SYSTEM_VARIABLES)
            ->willReturn([
                SystemConfigInterface::VAR_ENV_RELATIONSHIPS => 'MAGENTO_CLOUD_RELATIONSHIPS',
                SystemConfigInterface::VAR_ENV_ROUTES => 'MAGENTO_CLOUD_ROUTES',
                SystemConfigInterface::VAR_ENV_VARIABLES => 'MAGENTO_CLOUD_VARIABLES',
                SystemConfigInterface::VAR_ENV_APPLICATION => 'MAGENTO_CLOUD_APPLICATION',
                SystemConfigInterface::VAR_ENV_ENVIRONMENT => 'MAGENTO_CLOUD_ENVIRONMENT',
            ]);

        $this->variable = new Variables(
            $environmentReaderMock,
            $schemaMock
        );

        $this->environment = new Environment($this->variable, new Decoder());
    }

    /**
     * @param array $env
     * @param mixed $getEnv
     * @param string $key
     * @param mixed $default
     * @param mixed $expected
     * @dataProvider getDataProvider
     */
    public function testGet(array $env, $getEnv, string $key, $default, $expected)
    {
        $_ENV = $env;
        $getenvMock = $this->getFunctionMock('Magento\MagentoCloud\Config', 'getenv');
        $getenvMock->expects($this->any())
            ->willReturn($getEnv);

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
                false,
                'some_key',
                null,
                'some_value',
            ],
            'empty value' => [
                [],
                false,
                'some_key',
                null,
                null,
            ],
            'empty value with default' => [
                [],
                false,
                'some_key',
                'some_new_value',
                'some_new_value',
            ],
            'empty value with getenv with default' => [
                [],
                base64_encode(json_encode('getenv_value')),
                'some_key',
                'some_new_value',
                'getenv_value',
            ],
            'string value with getenv with default' => [
                ['some_key' => base64_encode(json_encode('some_value'))],
                base64_encode(json_encode('getenv_value')),
                'some_key',
                'some_new_value',
                'some_value',
            ],
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
