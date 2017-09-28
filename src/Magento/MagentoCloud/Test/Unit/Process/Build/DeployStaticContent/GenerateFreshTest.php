<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build\DeployStaticContent;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\Build\DeployStaticContent\GenerateFresh;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\ArrayManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Build as BuildConfig;

/**
 * @inheritdoc
 */
class GenerateFreshTest extends TestCase
{
    /**
     * @var GenerateFresh
     */
    private $process;

    /**
     * @var ShellInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shellMock;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var Environment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $environmentMock;

    /**
     * @var DirectoryList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $directoryListMock;

    /**
     * @var BuildConfig|\PHPUnit_Framework_MockObject_MockObject
     */
    private $buildConfigMock;

    /**
     * @var ArrayManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $arrayManagerMock;

    /**
     * @var MagentoVersion|\PHPUnit_Framework_MockObject_MockObject
     */
    private $magentoVersionMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->environmentMock = $this->createMock(Environment::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->buildConfigMock = $this->createMock(BuildConfig::class);
        $this->arrayManagerMock = $this->createMock(ArrayManager::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->process = new GenerateFresh(
            $this->shellMock,
            $this->loggerMock,
            $this->environmentMock,
            $this->directoryListMock,
            $this->buildConfigMock,
            $this->arrayManagerMock,
            $this->magentoVersionMock
        );
    }

    public function testExecute()
    {
        $this->buildConfigMock->method('get')
            ->willReturnMap([
                [BuildConfig::OPT_SCD_THREADS, 0, 3],
            ]);
        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn(__DIR__ . '/_files/');
        $flattenConfig = [
            'scopes' => [
                'websites' => [],
                'stores' => [],
            ],
        ];
        $this->arrayManagerMock->method('flatten')
            ->willReturn([
                'scopes' => [
                    'websites' => [],
                    'stores' => [],
                ],
            ]);
        $this->arrayManagerMock->expects($this->exactly(2))
            ->method('filter')
            ->willReturnMap([
                [$flattenConfig, 'general/locale/code', true, ['fr_FR']],
                [$flattenConfig, 'admin_user/locale/code', false, ['es_ES']],
            ]);
        $this->environmentMock->method('getAdminLocale')
            ->willReturn('ua_UA');
        $this->loggerMock->method('info')
            ->withConsecutive(
                ["Generating static content for locales: ua_UA fr_FR es_ES en_US\nUsing 3 Threads"]
            );
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn(true);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with(
                "printf 'php ./bin/magento setup:static-content:deploy -f ua_UA\n"
                . "php ./bin/magento setup:static-content:deploy -f fr_FR\n"
                . "php ./bin/magento setup:static-content:deploy -f es_ES\n"
                . "php ./bin/magento setup:static-content:deploy -f en_US\n"
                . "' | xargs -I CMD -P 3 bash -c CMD"
            );

        $this->process->execute();
    }
}
