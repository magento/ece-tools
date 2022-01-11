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
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
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
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->outputMock = $this->getMockForAbstractClass(OutputInterface::class);
        $this->outputFormatterMock = $this->getMockForAbstractClass(OutputFormatterInterface::class);
        $this->outputMock->expects($this->any())
            ->method('getFormatter')
            ->willReturn($this->outputFormatterMock);

        $this->renderer = new Renderer($this->loggerMock, $this->environmentMock);
    }

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
        $this->outputMock->expects($this->atLeast(8))
            ->method('writeln')
            ->withConsecutive(
                [PHP_EOL . '<info>Magento Cloud Services:</info>'],
                [$this->anything()],
                [$this->matchesRegularExpression('|Service configuration.*?Value|')],
                [$this->anything()],
                [$this->stringContains('service1')],
                [$this->anything()],
                [$this->matchesRegularExpression('|option1.*?value1|')],
                [$this->matchesRegularExpression('|option2.*?value2|')]
            );

        $this->renderer->printRelationships($this->outputMock);
    }

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
        $this->outputMock->expects($this->atLeast(8))
            ->method('writeln')
            ->withConsecutive(
                [PHP_EOL . '<info>Magento Cloud Routes:</info>'],
                [$this->anything()],
                [$this->matchesRegularExpression('|Route configuration.*?Value|')],
                [$this->anything()],
                [$this->stringContains('route1')],
                [$this->anything()],
                [$this->anything()],
                [$this->matchesRegularExpression('|option1.*?value1|')]
            );

        $this->renderer->printRoutes($this->outputMock);
    }

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
        $this->outputMock->expects($this->atLeast(10))
            ->method('writeln')
            ->withConsecutive(
                [PHP_EOL . '<info>Magento Cloud Environment Variables:</info>'],
                [$this->anything()],
                [$this->matchesRegularExpression('|Variable name.*?Value|')],
                [$this->anything()],
                [$this->matchesRegularExpression('|variable1.*?value1|')],
                [$this->matchesRegularExpression('|variable2.*?null|')],
                [$this->matchesRegularExpression('|variable3.*?true|')],
                [$this->stringContains('variable4')],
                [$this->matchesRegularExpression('|option1.*?false|')],
                [$this->matchesRegularExpression('|option2.*?optionValue2|')]
            );

        $this->renderer->printVariables($this->outputMock);
    }
}
