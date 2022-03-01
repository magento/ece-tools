<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\Deploy\SetCryptKey;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SetCryptKeyTest extends TestCase
{
    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ConfigReader|MockObject
     */
    private $configReaderMock;

    /**
     * @var ConfigWriter|MockObject
     */
    private $configWriterMock;

    /**
     * @var SetCryptKey
     */
    private $step;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);

        $this->step = new SetCryptKey(
            $this->environmentMock,
            $this->loggerMock,
            $this->configReaderMock,
            $this->configWriterMock
        );
    }

    /**
     * @throws StepException
     */
    public function testConfigUpdate(): void
    {
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->environmentMock->expects($this->once())
            ->method('getCryptKey')
            ->willReturn('TWFnZW50byBSb3g=');
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Checking existence of encryption key'],
                [sprintf('Setting encryption key from %s', Environment::VARIABLE_CRYPT_KEY)]
            );
        $this->configWriterMock->expects($this->once())
            ->method('update')
            ->with(['crypt' => ['key' => 'TWFnZW50byBSb3g=']]);

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testConfigUpdateWithError(): void
    {
        $errorMsg = 'Some error';
        $errorCode = 11111;
        $exception = new FileSystemException($errorMsg, $errorCode);
        $this->expectException(StepException::class);
        $this->expectExceptionCode(Error::DEPLOY_ENV_PHP_IS_NOT_WRITABLE);
        $this->expectExceptionMessage($errorMsg);

        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->environmentMock->expects($this->once())
            ->method('getCryptKey')
            ->willReturn('TWFnZW50byBSb3g=');
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Checking existence of encryption key'],
                [sprintf('Setting encryption key from %s', Environment::VARIABLE_CRYPT_KEY)]
            );
        $this->configWriterMock->expects($this->once())
            ->method('update')
            ->with(['crypt' => ['key' => 'TWFnZW50byBSb3g=']])
            ->willThrowException($exception);

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testEnvironmentVariableNotSet(): void
    {
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->environmentMock->expects($this->once())
            ->method('getCryptKey')
            ->willReturn('');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Checking existence of encryption key');
        $this->configWriterMock->expects($this->never())
            ->method('update');

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testKeyAlreadySet(): void
    {
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn(['crypt' => ['key' => 'QmVuIHd1eiBoZXJl']]);
        $this->environmentMock->expects($this->never())
            ->method('getCryptKey');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Checking existence of encryption key');
        $this->configWriterMock->expects($this->never())
            ->method('update');

        $this->step->execute();
    }
}
