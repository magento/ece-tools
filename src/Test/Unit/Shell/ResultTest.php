<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Shell;

use Magento\MagentoCloud\Shell\Result;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Process\Process;

/**
 * @inheritdoc
 */
class ResultTest extends TestCase
{
    /**
     * @var Result
     */
    private $result;

    /**
     * @var Process|MockObject
     */
    private $processMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->processMock = $this->createMock(Process::class);

        $this->result = new Result($this->processMock);
    }

    public function testGetExitCode()
    {
        $this->processMock->expects($this->once())
            ->method('getExitCode')
            ->willReturn(3);

        $this->assertEquals(3, $this->result->getExitCode());
    }

    /**
     * @param string $processOutput
     * @param array $expectedResult
     * @dataProvider getOutputDataProvider
     */
    public function testGetOutput(string $processOutput, array $expectedResult)
    {
        $this->processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn($processOutput);

        $this->assertEquals($expectedResult, $this->result->getOutput());
    }

    /**
     * @return array
     */
    public function getOutputDataProvider(): array
    {
        return [
            'output without new lines' => [
                'processOutput' => 'some output',
                'expectedResult' => ['some output'],
            ],
            'output with new lines' => [
                'processOutput' => "some output\nline2\nline3\n",
                'expectedResult' => ['some output', 'line2', 'line3'],
            ],
        ];
    }

    public function testGetOutputWithLogicException()
    {
        $this->processMock->expects($this->once())
            ->method('getOutput')
            ->willThrowException(new LogicException('some error'));

        $this->assertEmpty($this->result->getOutput());
    }
}
