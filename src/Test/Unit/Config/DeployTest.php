<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config;

use Illuminate\Config\Repository;
use Magento\MagentoCloud\Config\RepositoryFactory;
use Magento\MagentoCloud\Config\Deploy;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @inheritdoc
 */
class DeploydTest extends TestCase
{
    /**
     * @var Deploy
     */
    private $deploy;

    /**
     * @var Deploy\Reader|MockObject
     */
    private $readerMock;

    /**
     * @var Deploy\Writer|MockObject
     */
    private $writerMock;

    /**
     * @var RepositoryFactory|MockObject
     */
    private $factoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->readerMock = $this->createMock(Deploy\Reader::class);
        $this->writerMock = $this->createMock(Deploy\Writer::class);
        $this->factoryMock = $this->createMock(RepositoryFactory::class);

        $this->deploy = new Deploy($this->readerMock, $this->writerMock, $this->factoryMock);
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
        $this->factoryMock->expects($this->once())
            ->method('create')
            ->with($config)
            ->willReturn(new Repository($config));

        $this->assertSame('value1', $this->deploy->get('key1'));
        $this->assertSame('value2', $this->deploy->get('key2'));
        $this->assertNull($this->deploy->get('undefined'));
        $this->assertSame('default value', $this->deploy->get('missing_key', 'default value'));
    }

    public function testSet()
    {
        $configInit = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];
        $configFinal = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];

        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn($configInit);
        $this->writerMock->expects($this->once())
            ->method('update')
            ->with($configFinal);
        $this->factoryMock->expects($this->once())
            ->method('create')
            ->with($configInit)
            ->willReturn(new Repository($configInit));

        $this->deploy->set('key3', 'value3');
        $this->assertSame('value3', $this->deploy->get('key3'));
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
        $this->factoryMock->expects($this->once())
            ->method('create')
            ->with($config)
            ->willReturn(new Repository($config));

        $this->assertSame($config, $this->deploy->all());
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
        $this->factoryMock->expects($this->once())
            ->method('create')
            ->with($config)
            ->willReturn(new Repository($config));

        $this->assertTrue($this->deploy->has('key1'));
        $this->assertTrue($this->deploy->has('key2'));
        $this->assertFalse($this->deploy->has('key3'));
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
        $this->factoryMock->expects($this->exactly(2))
            ->method('create')
            ->with($config)
            ->willReturn(new Repository($config));

        $this->deploy->all();
        $this->deploy->reset();
        $this->deploy->all();
    }

    public function testUpdate()
    {
        $this->writerMock->expects($this->once())
            ->method('update')
            ->with(['some' => 'config']);

        $this->deploy->update(['some' => 'config']);
    }
}
