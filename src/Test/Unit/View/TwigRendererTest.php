<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\View;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\View\TwigRenderer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class TwigRendererTest extends TestCase
{
    /**
     * @var TwigRenderer
     */
    private $renderer;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryList;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->directoryList = $this->createMock(DirectoryList::class);

        $this->directoryList->method('getViews')
            ->willReturn(__DIR__ . '/_files');

        $this->renderer = new TwigRenderer(
            $this->directoryList
        );
    }

    public function testRender()
    {
        $this->assertContains('Some content', $this->renderer->render('sample.html.twig'));
    }
}
