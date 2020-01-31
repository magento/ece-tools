<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Build;

use Magento\MagentoCloud\Config\Validator\Build\BalerSupport;
use Magento\MagentoCloud\Config\Magento\Shared\ReaderInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\Validator\Result;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\ExecutableFinder;

/**
 * @inheritdoc
 */
class BalerSupportTest extends TestCase
{
    /**
     * @var BalerSupport
     */
    private $validator;

    /**
     * @var ReaderInterface|MockObject
     */
    private $configReaderMock;

    /**
     * @var ExecutableFinder|MockObject
     */
    private $execFinderMock;

    /**
     * @var Validator\ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resultFactoryMock = $this->createConfiguredMock(Validator\ResultFactory::class, [
            'success' => $this->createMock(Result\Success::class),
            'error' => $this->createMock(Result\Error::class),
        ]);
        $this->execFinderMock = $this->createMock(ExecutableFinder::class);
        $this->configReaderMock = $this->createMock(ReaderInterface::class);

        $this->validator = new BalerSupport($this->resultFactoryMock, $this->execFinderMock, $this->configReaderMock);
    }

    public function testValidatorPasses(): void
    {
        $this->execFinderMock->method('find')
            ->with('baler')
            ->willReturn('/path/to/baler');
        $this->configReaderMock->method('read')
            ->willReturn([
                'modules' => ['Magento_Baler' => '1'],
                'system' => [
                    'default' => [
                        'dev' => [
                            'js' => [
                                'enable_baler_js_bundling' => '1',
                                'minify_files' => '0',
                                'enable_js_bundling' => '0',
                                'merge_files' => '0',
                            ],
                        ],
                    ],
                ],
            ]);

        $this->assertInstanceOf(Result\Success::class, $this->validator->validate());
    }

    public function testBalerNotInstalled(): void
    {
        $this->execFinderMock->method('find')
            ->with('baler')
            ->willReturn(null);
        $this->configReaderMock->method('read')
            ->willReturn([
                'modules' => ['Magento_Baler' => '1'],
                'system' => [
                    'default' => [
                        'dev' => [
                            'js' => [
                                'enable_baler_js_bundling' => '1',
                                'minify_files' => '0',
                                'enable_js_bundling' => '0',
                                'merge_files' => '0',
                            ],
                        ],
                    ],
                ],
            ]);
        $this->resultFactoryMock->method('error')
            ->with(
                'Baler JS bundling cannot be used because of the following issues:',
                'Path to baler executable could not be found.'
                    . ' The Node package may not be installed or may not be linked.'
            );

        $this->assertInstanceOf(Result\Error::class, $this->validator->validate());
    }

    public function testNoConfig(): void
    {
        $this->execFinderMock->method('find')
            ->with('baler')
            ->willReturn('/path/to/baler');
        $this->configReaderMock->method('read')
            ->willReturn([]);
        $this->resultFactoryMock->method('error')
            ->with(
                'Baler JS bundling cannot be used because of the following issues:',
                implode(PHP_EOL, [
                    'The Magento_Baler module is not installed or is disabled.',
                    'The Magento config dev/js/enable_baler_js_bundling must be enabled in app/etc/config.php.',
                    'The Magento config dev/js/minify_files must be disabled in app/etc/config.php.',
                    'The Magento config dev/js/enable_js_bundling must be disabled in app/etc/config.php.',
                    'The Magento config dev/js/merge_files must be disabled in app/etc/config.php.',
                ])
            );

        $this->assertInstanceOf(Result\Error::class, $this->validator->validate());
    }

    public function testInvalidConfig(): void
    {
        $this->execFinderMock->method('find')
            ->with('baler')
            ->willReturn('/path/to/baler');
        $this->configReaderMock->method('read')
            ->willReturn([
                'modules' => ['Magento_Baler' => '0'],
                'system' => [
                    'default' => [
                        'dev' => [
                            'js' => [
                                'enable_baler_js_bundling' => '0',
                                'minify_files' => '1',
                                'enable_js_bundling' => '1',
                                'merge_files' => '1',
                            ],
                        ],
                    ],
                ],
            ]);
        $this->resultFactoryMock->method('error')
            ->with(
                'Baler JS bundling cannot be used because of the following issues:',
                implode(PHP_EOL, [
                    'The Magento_Baler module is not installed or is disabled.',
                    'The Magento config dev/js/enable_baler_js_bundling must be enabled in app/etc/config.php.',
                    'The Magento config dev/js/minify_files must be disabled in app/etc/config.php.',
                    'The Magento config dev/js/enable_js_bundling must be disabled in app/etc/config.php.',
                    'The Magento config dev/js/merge_files must be disabled in app/etc/config.php.',
                ])
            );

        $this->assertInstanceOf(Result\Error::class, $this->validator->validate());
    }
}
