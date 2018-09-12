<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Environment;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Environment\Reader;
use Magento\MagentoCloud\Filesystem\ConfigFileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\SystemList;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ReaderTest extends TestCase
{
    use PHPMock;

    /**
     * @var SystemList|MockObject
     */
    private $systemListMock;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var ConfigFileList|MockObject
     */
    private $configFileListMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        /**
         * This lines are required for proper running of Magento\MagentoCloud\Test\Unit\Filesystem\Driver\FileTest
         */
        self::defineFunctionMock('Magento\MagentoCloud\Filesystem\Driver', 'file_get_contents');
        self::defineFunctionMock('Magento\MagentoCloud\Filesystem\Driver', 'file_exists');

        $this->systemListMock = $this->createMock(SystemList::class);
        $this->environmentMock = $this->createMock(Environment::class);
        $this->configFileListMock = $this->createMock(ConfigFileList::class);
        $this->fileMock = $this->createPartialMock(File::class, []);

        $this->reader = new Reader(
            $this->systemListMock,
            $this->environmentMock,
            $this->configFileListMock,
            $this->fileMock
        );
    }

    public function testRead()
    {
        $baseDir = __DIR__ . '/_file/';

        $this->configFileListMock->expects($this->once())
            ->method('getEnvConfig')
            ->willReturn($baseDir . '/.magento.env.yaml');
        $this->systemListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($baseDir);
        $this->environmentMock->expects($this->once())
            ->method('getBranchName')
            ->willReturn('test-branch');

        $this->reader->read();
        $this->assertEquals(
            [
                'stage' => [
                    'global' => ['SCD_ON_DEMAND' => false, 'UPDATE_URLS' => false],
                    'deploy' => ['DATABASE_CONFIGURATION' => ['host' => 'localhost'], 'SCD_THREADS' => 3],
                    'build' => ['SCD_THREADS' => 2],
                ],
                'log' => [
                    'gelf' => [
                        'min_level' => 'info',
                        'use_default_formatter' => true,
                        'additional' => ['project' => 'project'],
                    ],
                    'syslog' => ['ident' => 'ident-branch', 'facility' => 7],
                ],
            ],
            $this->reader->read()
        );
    }

    public function testReadBranchConfigNotExists()
    {
        $baseDir = __DIR__ . '/_file/';

        $this->configFileListMock->expects($this->once())
            ->method('getEnvConfig')
            ->willReturn($baseDir . '/.magento.env.yaml');
        $this->systemListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($baseDir);
        $this->environmentMock->expects($this->once())
            ->method('getBranchName')
            ->willReturn('not-exist');

        $this->assertEquals(
            [
                'stage' => [
                    'global' => ['SCD_ON_DEMAND' => true, 'UPDATE_URLS' => false],
                    'deploy' => [
                        'DATABASE_CONFIGURATION' => [
                            'host' => '127.0.0.1',
                            'port' => '3306',
                            'schema' => 'test_schema',
                        ],
                        'SCD_THREADS' => 5,
                    ],
                ],
                'log' => [
                    'gelf' => [
                        'min_level' => 'info',
                        'use_default_formatter' => true,
                        'additional' => ['project' => 'project', 'app_id' => 'app'],
                    ],
                ],
            ],
            $this->reader->read()
        );
    }

    public function testReadBranchConfigWithEmptySectionAndStage()
    {
        $baseDir = __DIR__ . '/_file/';

        $this->configFileListMock->expects($this->once())
            ->method('getEnvConfig')
            ->willReturn($baseDir . '/.magento.env.yaml');
        $this->systemListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($baseDir);
        $this->environmentMock->expects($this->once())
            ->method('getBranchName')
            ->willReturn('test-branch-emty');

        $this->assertEquals(
            [
                'stage' => [
                    'global' => ['SCD_ON_DEMAND' => true, 'UPDATE_URLS' => false],
                    'deploy' => [
                        'DATABASE_CONFIGURATION' => [
                            'host' => '127.0.0.1',
                            'port' => '3306',
                            'schema' => 'test_schema',
                        ],
                        'SCD_THREADS' => 5,
                    ],
                ],
                'log' => [
                    'gelf' => [
                        'min_level' => 'info',
                        'use_default_formatter' => true,
                        'additional' => ['project' => 'project', 'app_id' => 'app'],
                    ],
                ],
            ],
            $this->reader->read()
        );
    }
}
