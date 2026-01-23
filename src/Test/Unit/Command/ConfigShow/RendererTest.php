<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command\ConfigShow;

use Codeception\PHPUnit\TestCase;
use Magento\MagentoCloud\Command\ConfigShow\Renderer;
use Magento\MagentoCloud\Config\Environment;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class RendererTest extends TestCase
{
    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var OutputInterface|MockObject
     */
    private $outputMock;

    /**
     * @var OutputFormatterInterface|MockObject
     */
    private $outputFormatterMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->outputMock = $this->createMock(OutputInterface::class);
        $this->outputFormatterMock = $this->createMock(OutputFormatterInterface::class);
        $this->outputMock->expects($this->any())
            ->method('getFormatter')
            ->willReturn($this->outputFormatterMock);

        $this->renderer = new Renderer($this->loggerMock, $this->environmentMock);
    }

    /**
     * @SuppressWarnings("PHPMD.NPathComplexity")
     */
    public function testPrintRelationships()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationships')
            ->willReturn([
                'service1' => [[
                    'option1' => 'value1',
                    'option2' => 'value2'
                ]]
            ]);
        $this->outputFormatterMock->expects(self::any())
            ->method('format')
            ->willReturnArgument(0);
        $invokedCount = $this->atLeast(8);
        $this->outputMock->expects($invokedCount)
            ->method('writeln')
            // withConsecutive() alternative.
            ->willReturnCallback(function ($parameters) use ($invokedCount) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertSame(PHP_EOL . '<info>Magento Cloud Services:</info>', $parameters);
                }
        
                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertThat($parameters, $this->anything());
                }

                if ($invokedCount->numberOfInvocations() === 3) {
                    $this->assertMatchesRegularExpression('|Service configuration.*?Value|', $parameters);
                }

                if ($invokedCount->numberOfInvocations() === 4) {
                    $this->assertThat($parameters, $this->anything());
                }

                if ($invokedCount->numberOfInvocations() === 5) {
                    $this->assertStringContainsString('service1', $parameters);
                }

                if ($invokedCount->numberOfInvocations() === 6) {
                    $this->assertThat($parameters, $this->anything());
                }

                if ($invokedCount->numberOfInvocations() === 7) {
                    $this->assertMatchesRegularExpression('|option1.*?value1|', $parameters);
                }

                if ($invokedCount->numberOfInvocations() === 8) {
                    $this->assertMatchesRegularExpression('|option2.*?value2|', $parameters);
                }
            });

        $this->renderer->printRelationships($this->outputMock);
    }

    /**
     * @SuppressWarnings("PHPMD.NPathComplexity")
     */
    public function testPrintRoutes()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRoutes')
            ->willReturn([
                'route1' => [[
                    'option1' => 'value1'
                ]]
            ]);
        $this->outputFormatterMock->expects(self::any())
            ->method('format')
            ->willReturnArgument(0);
        $invokedCount = $this->atLeast(8);
        $this->outputMock->expects($invokedCount)
            ->method('writeln')
            // withConsecutive() alternative.
            ->willReturnCallback(function ($parameters) use ($invokedCount) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertSame(PHP_EOL . '<info>Magento Cloud Routes:</info>', $parameters);
                }
        
                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertThat($parameters, $this->anything());
                }

                if ($invokedCount->numberOfInvocations() === 3) {
                    $this->assertMatchesRegularExpression('|Route configuration.*?Value|', $parameters);
                }

                if ($invokedCount->numberOfInvocations() === 4) {
                    $this->assertThat($parameters, $this->anything());
                }

                if ($invokedCount->numberOfInvocations() === 5) {
                    $this->assertStringContainsString('route1', $parameters);
                }

                if ($invokedCount->numberOfInvocations() === 6) {
                    $this->assertThat($parameters, $this->anything());
                }

                if ($invokedCount->numberOfInvocations() === 7) {
                    $this->assertThat($parameters, $this->anything());
                }

                if ($invokedCount->numberOfInvocations() === 8) {
                    $this->assertMatchesRegularExpression('|option1.*?value1|', $parameters);
                }
            });

        $this->renderer->printRoutes($this->outputMock);
    }

    /**
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     * @SuppressWarnings("PHPMD.NPathComplexity")
     */
    public function testPrintVariables()
    {
        $this->environmentMock->expects($this->once())
            ->method('getVariables')
            ->willReturn([
                'variable1' => 'value1',
                'variable2' => 'null',
                'variable3' => true,
                'variable4' => [
                    'option1' => false,
                    'option2' => 'optionValue2'
                ],
            ]);
        $this->outputFormatterMock->expects(self::any())
            ->method('format')
            ->willReturnArgument(0);
        $invokedCount = $this->atLeast(8);
        $this->outputMock->expects($invokedCount)
            ->method('writeln')
            // withConsecutive() alternative.
            ->willReturnCallback(function ($parameters) use ($invokedCount) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertSame(PHP_EOL . '<info>Magento Cloud Environment Variables:</info>', $parameters);
                }
        
                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertThat($parameters, $this->anything());
                }

                if ($invokedCount->numberOfInvocations() === 3) {
                    $this->assertMatchesRegularExpression('|Variable name.*?Value|', $parameters);
                }

                if ($invokedCount->numberOfInvocations() === 4) {
                    $this->assertThat($parameters, $this->anything());
                }

                if ($invokedCount->numberOfInvocations() === 5) {
                    $this->assertMatchesRegularExpression('|variable1.*?value1|', $parameters);
                }

                if ($invokedCount->numberOfInvocations() === 6) {
                    $this->assertMatchesRegularExpression('|variable2.*?null|', $parameters);
                }

                if ($invokedCount->numberOfInvocations() === 7) {
                    $this->assertMatchesRegularExpression('|variable3.*?true|', $parameters);
                }

                if ($invokedCount->numberOfInvocations() === 8) {
                    $this->assertStringContainsString('variable4', $parameters);
                }

                if ($invokedCount->numberOfInvocations() === 9) {
                    $this->assertMatchesRegularExpression('|option1.*?false|', $parameters);
                }

                if ($invokedCount->numberOfInvocations() === 10) {
                    $this->assertMatchesRegularExpression('|option2.*?optionValue2|', $parameters);
                }
            });

        $this->renderer->printVariables($this->outputMock);
    }
}
