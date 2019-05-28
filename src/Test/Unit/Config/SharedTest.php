<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config;

use Illuminate\Config\Repository;
use Magento\MagentoCloud\Config\RepositoryFactory;
use Magento\MagentoCloud\Config\Shared;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class SharedTest extends TestCase
{
    /**
     * @var Shared
     */
    private $shared;

    /**
     * @var Shared\Reader|\PHPUnit_Framework_MockObject_MockObject
     */
    private $readerMock;

    /**
     * @var Shared\Writer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $writerMock;

    /**
     * @var RepositoryFactory|Mock
     */
    private $repositoryFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->readerMock = $this->createMock(Shared\Reader::class);
        $this->writerMock = $this->createMock(Shared\Writer::class);
        $this->repositoryFactoryMock = $this->createMock(RepositoryFactory::class);

        $this->shared = new Shared($this->readerMock, $this->writerMock, $this->repositoryFactoryMock);
    }

    public function testGet()
    {
        $config = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn($config);
        $this->repositoryFactoryMock->expects($this->once())
            ->method('create')
            ->with($config)
            ->willReturn(new Repository($config));

        $this->assertSame('value1', $this->shared->get('key1'));
        $this->assertSame('value2', $this->shared->get('key2'));
        $this->assertNull($this->shared->get('undefined'));
    }

    public function testAll()
    {
        $config = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn($config);
        $this->repositoryFactoryMock->expects($this->once())
            ->method('create')
            ->with($config)
            ->willReturn(new Repository($config));

        $this->assertSame($config, $this->shared->all());
    }

    public function testHas()
    {
        $config = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn($config);
        $this->repositoryFactoryMock->expects($this->once())
            ->method('create')
            ->with($config)
            ->willReturn(new Repository($config));

        $this->assertTrue($this->shared->has('key1'));
        $this->assertTrue($this->shared->has('key2'));
        $this->assertFalse($this->shared->has('key3'));
    }

    public function testReset()
    {
        $config = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $this->readerMock->expects($this->exactly(2))
            ->method('read')
            ->willReturn($config);
        $this->repositoryFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->with($config)
            ->willReturn(new Repository($config));

        $this->shared->all();
        $this->shared->reset();
        $this->shared->all();
    }

    public function testUpdate()
    {
        $this->writerMock->expects($this->once())
            ->method('update')
            ->with(['some' => 'config']);

        $this->shared->update(['some' => 'config']);
    }
}
