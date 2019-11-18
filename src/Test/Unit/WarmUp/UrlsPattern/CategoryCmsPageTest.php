<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\WarmUp\UrlsPattern;

use Codeception\PHPUnit\TestCase;
use Magento\MagentoCloud\WarmUp\UrlsPattern\CategoryCmsPage;
use Magento\MagentoCloud\WarmUp\UrlsPattern\CommandArgumentBuilder;
use Magento\MagentoCloud\WarmUp\UrlsPattern\ConfigShowUrlCommand;
use Magento\MagentoCloud\WarmUp\UrlsPattern\ParseResultException;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @inheritDoc
 */
class CategoryCmsPageTest extends TestCase
{
    /**
     * @var CategoryCmsPage
     */
    private $categoryCmsPage;

    /**
     * @var ConfigShowUrlCommand|MockObject
     */
    private $configShowUrlCommandMock;

    /**
     * @var CommandArgumentBuilder|MockObject
     */
    private $argumentBuilderMock;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->configShowUrlCommandMock = $this->createMock(ConfigShowUrlCommand::class);
        $this->argumentBuilderMock = $this->createMock(CommandArgumentBuilder::class);

        $this->categoryCmsPage = new CategoryCmsPage($this->configShowUrlCommandMock, $this->argumentBuilderMock);
    }

    /**
     * @param string $pattern
     * @param array $expectedUrls
     * @dataProvider getUrlsDataProvider
     * @throws ParseResultException
     */
    public function testGetUrls(string $pattern, array $expectedUrls)
    {
        $urls = [
            'http://example.com/',
            'http://example2.com/',
            'http://site.com/',
            'http://test.com/'
        ];

        $this->argumentBuilderMock->expects($this->once())
            ->method('generate')
            ->with('cms-page', '*')
            ->willReturn(['--entity=cms-page']);
        $this->configShowUrlCommandMock->expects($this->once())
            ->method('execute')
            ->with(['--entity=cms-page'])
            ->willReturn($urls);

        $this->assertEquals(
            $expectedUrls,
            array_values($this->categoryCmsPage->getUrls('cms-page', $pattern, '*'))
        );
    }

    /**
     * @return array
     */
    public function getUrlsDataProvider(): array
    {
        return [
            [
                '*',
                [
                    'http://example.com/',
                    'http://example2.com/',
                    'http://site.com/',
                    'http://test.com/'
                ],
            ],
            [
                '/example/',
                [
                    'http://example.com/',
                    'http://example2.com/',
                ],
            ],
            [
                '/site/',
                [
                    'http://site.com/',
                ],
            ],
        ];
    }
}
