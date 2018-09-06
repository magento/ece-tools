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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ReaderTest extends TestCase
{
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
     * @var Reader
     */
    private $reader;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->systemListMock = $this->createMock(SystemList::class);
        $this->environmentMock = $this->createMock(Environment::class);
        $this->configFileListMock = $this->createMock(ConfigFileList::class);

        $this->reader = new Reader(
            $this->systemListMock,
            $this->environmentMock,
            $this->configFileListMock,
            new File()
        );
    }

    public function testRead()
    {
        $baseDir = __DIR__ . '/_file/';

        $this->configFileListMock->expects($this->once())
            ->method('getEnvConfig')
            ->willReturn($baseDir . '/.magento.env.yaml');
        $this->systemListMock->expects($this->once())
            ->method('getRoot')
            ->willReturn($baseDir);
        $this->environmentMock->expects($this->once())
            ->method('getBranchName')
            ->willReturn('test-branch');

        $this->reader->read();
        $this->assertEquals(
            ['stage' => ['global' => ['SCD_ON_DEMAND' => false, 'UPDATE_URLS' => false]]],
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
            ->method('getRoot')
            ->willReturn($baseDir);
        $this->environmentMock->expects($this->once())
            ->method('getBranchName')
            ->willReturn('not-exist');

        $this->assertEquals(
            ['stage' => ['global' => ['SCD_ON_DEMAND' => true, 'UPDATE_URLS' => false]]],
            $this->reader->read()
        );
    }
}
