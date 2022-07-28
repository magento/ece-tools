<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\Amqp;
use Magento\MagentoCloud\Config\Amqp as AmqpConfig;

/**
 * @inheritdoc
 */
class AmqpTest extends TestCase
{
    /**
     * @var Amqp
     */
    private $step;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ConfigWriter|MockObject
     */
    private $configWriterMock;

    /**
     * @var ConfigReader|MockObject
     */
    private $configReaderMock;

    /**
     * @var AmqpConfig|MockObject
     */
    private $amqpConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->amqpConfigMock = $this->createMock(AmqpConfig::class);

        $this->step = new Amqp(
            $this->configReaderMock,
            $this->configWriterMock,
            $this->loggerMock,
            $this->amqpConfigMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithoutAmqp(): void
    {
        $config = ['some config'];

        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($config);
        $this->amqpConfigMock->expects($this->once())
            ->method('getConfig')
            ->willReturn([]);
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->configWriterMock->expects($this->never())
            ->method('create')
            ->with($config);

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteAddUpdate(): void
    {
        $config = ['some config'];
        $amqpConfig = [
            'amqp' => [
                'host' => 'localhost',
                'port' => '777',
                'username' => 'login',
                'password' => 'pswd',
                'vhost' => 'virtualhost',
            ]
        ];
        $resultConfig = [
            'some config',
            'queue' => $amqpConfig
        ];

        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($config);
        $this->amqpConfigMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($amqpConfig);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating env.php AMQP configuration.');
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with($resultConfig);

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteRemoveAmqp(): void
    {
        $config = [
            'some config',
            'queue' => [
                'amqp' => [
                    'host' => 'localhost',
                    'port' => '777',
                    'user' => 'login',
                    'password' => 'pswd',
                    'virtualhost' => '/',
                ],
            ],
        ];
        $expectedConfig = ['some config'];

        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($config);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Removing queue configuration from env.php.');
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with($expectedConfig);

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithExceptionInRead()
    {
        $exceptionMsg = 'Error';
        $exceptionCode = 111;

        $this->expectException(StepException::class);
        $this->expectExceptionMessage($exceptionMsg);
        $this->expectExceptionCode($exceptionCode);

        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willThrowException(new GenericException($exceptionMsg, $exceptionCode));

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithExceptionInWrite()
    {
        $exceptionMsg = 'Some error';
        $exceptionCode = 11111;
        $this->expectException(StepException::class);
        $this->expectExceptionCode(Error::DEPLOY_ENV_PHP_IS_NOT_WRITABLE);
        $this->expectExceptionMessage($exceptionMsg);

        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn(['queue' => ['some data']]);

        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->willThrowException(new FileSystemException($exceptionMsg, $exceptionCode));

        $this->step->execute();
    }
}
