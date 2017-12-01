<?php
/**
 * Created by PhpStorm.
 * User: wgilbert
 * Date: 12/1/17
 * Time: 1:57 PM
 */

namespace Magento\MagentoCloud\Test\Unit\Filesystem\FlagFile;

use Magento\MagentoCloud\Filesystem\FlagFile\Base;
use Magento\MagentoCloud\Filesystem\FlagFile\RegenerateFlag;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class RegenerateFlagTest extends TestCase
{
    /**
     * @var Base|Mock
     */
    private $baseMock;

    /**
     * @var RegenerateFlag
     */
    private $flag;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->baseMock = $this->createMock(Base::class);
        $this->flag = new RegenerateFlag($this->baseMock);
    }

    public function testExists()
    {
        $this->baseMock->expects($this->once())
            ->method('exists')
            ->willReturn(true);

        $this->assertTrue($this->flag->exists());
    }

    public function testSet()
    {
        $this->baseMock->expects($this->once())
            ->method('set')
            ->willReturn(true);

        $this->assertTrue($this->flag->set());
    }

    public function testDelete()
    {
        $this->baseMock->expects($this->once())
            ->method('delete')
            ->willReturn(true);

        $this->assertTrue($this->flag->delete());
    }


    public function testGetPath()
    {
        $this->assertSame($this->flag::PATH, $this->flag->getPath());
    }

    public function testGetKey()
    {
        $this->assertSame($this->flag::KEY, $this->flag->getKey());
    }
}
