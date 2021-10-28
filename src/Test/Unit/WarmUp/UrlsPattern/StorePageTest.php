<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\WarmUp\UrlsPattern;

use Codeception\PHPUnit\TestCase;
use Magento\MagentoCloud\Util\UrlManager;
use Magento\MagentoCloud\WarmUp\UrlsPattern\StorePage;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @inheritDoc
 */
class StorePageTest extends TestCase
{
    /**
     * @var StorePage
     */
    private $storePage;

    /**
     * @var UrlManager|MockObject
     */
    private $urlManagerMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->urlManagerMock = $this->createMock(UrlManager::class);

        $this->storePage = new StorePage($this->urlManagerMock);
    }

    public function testGetUrls()
    {
        $this->urlManagerMock->expects($this->exactly(2))
            ->method('getStoreBaseUrl')
            ->withConsecutive(['store1'], ['store2'])
            ->willReturnOnConsecutiveCalls('http://store1.com/', 'http://store2.com');
        $this->urlManagerMock->expects($this->never())
            ->method('getBaseUrls');

        $this->assertEquals(
            [
                'http://store1.com/path/to/page.html',
                'http://store2.com/path/to/page.html',
            ],
            $this->storePage->getUrls('store-page', '/path/to/page.html', 'store1|store2')
        );
    }

    public function testGetUrlsAll()
    {
        $this->urlManagerMock->expects($this->never())
            ->method('getStoreBaseUrl');
        $this->urlManagerMock->expects($this->once())
            ->method('getBaseUrls')
            ->willReturn([
                'http://store1.com',
                'http://store2.com',
                'http://store3.com',
            ]);

        $this->assertEquals(
            [
                'http://store1.com/path/to/page.html',
                'http://store2.com/path/to/page.html',
                'http://store3.com/path/to/page.html',
            ],
            $this->storePage->getUrls('store-page', '/path/to/page.html', '*')
        );
    }
}
