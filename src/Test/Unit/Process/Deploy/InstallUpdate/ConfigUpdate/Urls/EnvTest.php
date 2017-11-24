<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate\Urls;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Urls\Env;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Deploy\Reader as EnvConfigReader;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Util\UrlManager;

/**
 * @inheritdoc
 */
class EnvTest extends TestCase
{
    /**
     * @var Env
     */
    private $process;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var UrlManager|Mock
     */
    private $urlManagerMock;

    /**
     * @var EnvConfigReader|Mock
     */
    private $envConfigReaderMock;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var FileList|Mock
     */
    private $fileListMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->urlManagerMock = $this->createMock(UrlManager::class);
        $this->envConfigReaderMock = $this->createMock(EnvConfigReader::class);
        $this->fileMock = $this->createMock(File::class);
        $this->fileListMock = $this->createMock(FileList::class);

        $this->process = new Env(
            $this->environmentMock,
            $this->loggerMock,
            $this->urlManagerMock,
            $this->envConfigReaderMock,
            $this->fileMock,
            $this->fileListMock
        );
    }

    /**
     * @param \PHPUnit_Framework_MockObject_Matcher_InvokedCount $loggerInfoExpects
     * @param array $urlManagerGetUrlsWillReturn
     * @param \PHPUnit_Framework_MockObject_Matcher_InvokedCount $fileExpectsFilePutContents
     * @dataProvider executeDataProvider
     */
    public function testExecute($loggerInfoExpects, $urlManagerGetUrlsWillReturn, $fileExpectsFilePutContents)
    {
        $configPath = __DIR__ . '/_files/env.php';
        $configEnv = include $configPath;
        $configEnvContent = file_get_contents($configPath);
        $configEnvForVerification = file_get_contents(__DIR__ . '/_files/env_.php');

        $this->loggerMock->expects($loggerInfoExpects)
            ->method('info')
            ->withConsecutive(
                ['Updating secure and unsecure URLs in app/etc/env.php file'],
                ['Replace host: [example1.com] => [example2.com]'],
                [sprintf('Write the updating configuration in %s file', $configPath)]
            );
        $this->envConfigReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($configEnv);
        $this->fileListMock->expects($this->once())
            ->method('getEnv')
            ->willReturn($configPath);
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->willReturn($configEnvContent);
        $this->urlManagerMock->expects($this->once())
            ->method('getUrls')
            ->willReturn($urlManagerGetUrlsWillReturn);
        $this->fileMock->expects($fileExpectsFilePutContents)
            ->method('filePutContents')
            ->with($configPath, $configEnvForVerification);

        $this->process->execute();
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'urls not equal' => [
                'loggerInfoExpects' => $this->exactly(3),
                'urlManagerGetUrlsWillReturn' => [
                    'secure' => ['' => 'https://example2.com/', '*' => 'https://subsite---example2.com'],
                    'unsecure' => ['' => 'http://example2.com/', '*' => 'http://subsite---example2.com'],
                ],
                'fileExpectsFilePutContents' => $this->once()
            ],
            'urls equal' => [
                'loggerInfoExpects' => $this->once(),
                'urlManagerGetUrlsWillReturn' => [
                    'secure' => ['' => 'https://example1.com/', '*' => 'https://subsite---example1.com'],
                    'unsecure' => ['' => 'http://example1.com/', '*' => 'http://subsite---example1.com'],
                ],
                'fileExpectsFilePutContents' => $this->never()
            ],
            'urls not exists' => [
                'loggerInfoExpects' => $this->once(),
                'urlManagerGetUrlsWillReturn' => [
                    'secure' => [],
                    'unsecure' => [],
                ],
                'fileExpectsFilePutContents' => $this->never()
            ],
        ];
    }
}
