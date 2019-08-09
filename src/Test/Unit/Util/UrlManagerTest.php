<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellFactory;
use Magento\MagentoCloud\Util\UrlManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class UrlManagerTest extends TestCase
{
    /**
     * @var UrlManager
     */
    private $manager;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var MagentoShell|MockObject
     */
    private $magentoShellMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->environmentMock = $this->createMock(Environment::class);
        $this->magentoShellMock = $this->createMock(MagentoShell::class);
        /** @var ShellFactory|MockObject $shellFactoryMock */
        $shellFactoryMock = $this->createMock(ShellFactory::class);
        $shellFactoryMock->expects($this->once())
            ->method('createMagento')
            ->willReturn($this->magentoShellMock);

        $this->manager = new UrlManager(
            $this->environmentMock,
            $this->loggerMock,
            $shellFactoryMock
        );
    }

    /**
     * @param array $routes
     * @dataProvider secureRouteDataProvider
     */
    public function testParseRoutesSecure(array $routes)
    {
        $this->environmentMock->expects($this->once())
            ->method('getRoutes')
            ->willReturn($routes);

        $this->assertArrayHasKey('secure', $this->manager->getUrls());
    }

    /**
     * @param array $routes
     * @dataProvider unsecureRouteDataProvider
     */
    public function testParseRoutesUnsecure(array $routes)
    {
        $this->environmentMock->expects($this->once())
            ->method('getRoutes')
            ->willReturn($routes);

        $this->assertArrayHasKey('unsecure', $this->manager->getUrls());
    }

    /**
     * @param array $secureRoute
     * @param string $expectedUrl
     * @dataProvider secureRouteDataProvider
     */
    public function testGetSecureUrlMethod(array $secureRoute, string $expectedUrl)
    {
        $this->environmentMock->expects($this->once())
            ->method('getRoutes')
            ->willReturn($secureRoute);

        $this->assertArrayHasKey($expectedUrl, $this->manager->getSecureUrls());
    }

    /**
     * @param array $unsecureRoute
     * @param string $expectedUrl
     * @dataProvider unsecureRouteDataProvider
     */
    public function testGetUnsecureUrlMethod(array $unsecureRoute, string $expectedUrl)
    {
        $this->environmentMock->expects($this->once())
            ->method('getRoutes')
            ->willReturn($unsecureRoute);

        $urls = $this->manager->getUnsecureUrls();

        $this->assertArrayHasKey($expectedUrl, $urls);
    }

    /**
     * @param array $unsecureRoute
     * @param $expectedUrl
     * @dataProvider noSecureRouteUrlDataProvider
     */
    public function testNoSecure(array $unsecureRoute, array $expectedUrl)
    {
        $this->environmentMock->expects($this->once())
            ->method('getRoutes')
            ->willReturn($unsecureRoute);

        $this->assertEquals($this->manager->getUrls()['secure'], $expectedUrl);
    }

    /**
     * @param array $secureRoute
     * @dataProvider secureRouteUrlDataProvider
     */
    public function testGetSecureUrl(array $secureRoute)
    {
        $this->environmentMock->expects($this->once())
            ->method('getRoutes')
            ->willReturn($secureRoute);
        $urls = $this->manager->getUrls();

        $this->assertEquals($urls['unsecure'], $urls['secure']);
    }

    /**
     * @param array $routes
     * @param array $expectedResult
     * @dataProvider getUrlsDataProvider
     */
    public function testGetUrls(array $routes, array $expectedResult)
    {
        $this->environmentMock->expects($this->once())
            ->method('getRoutes')
            ->willReturn($routes);

        $this->assertEquals($expectedResult, $this->manager->getUrls());
        // Lazy load.
        $this->assertEquals($expectedResult, $this->manager->getUrls());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Expected at least one valid unsecure or secure route. None found.
     */
    public function testGetUrlsException()
    {
        // No Mock so we get an exception indicating no URLS present.
        $this->manager->getUrls();
    }

    public function allRoutesDataProvider(): array
    {
        return [
            [
                $this->secureUrlExample(),
                $this->unsecureUrlExample(),
            ],
        ];
    }

    public function noSecureRouteUrlDataProvider(): array
    {
        return [
            [
                $this->unsecureUrlExample(),
                [
                    'example.com' => 'https://example.com/',
                ],
            ],
        ];
    }

    public function secureRouteDataProvider(): array
    {
        return [
            [
                $this->secureUrlExample(),
                'example.com',
            ],
        ];
    }

    public function unsecureRouteDataProvider(): array
    {
        return [
            [
                $this->unsecureUrlExample(),
                'example.com',
            ],
        ];
    }

    public function secureRouteUrlDataProvider(): array
    {
        return [
            [
                $this->secureUrlExample(),
            ],
        ];
    }

    public function unsecureRouteUrlDataProvider(): array
    {
        return [
            $this->secureUrlExample(),
        ];
    }

    private function secureUrlExample(): array
    {
        return [
            'https://example.com/' => [
                'original_url' => 'https://example.com/',
                'type' => 'upstream',
                'ssi' => [
                    'enabled' => false,
                ],
                'upstream' => 'mymagento',
                'cache' => [
                    'cookies' => ['*'],
                    'default_ttl' => 0,
                    'enabled' => true,
                    'headers' => [
                        'Accept',
                        'Accept-Language',
                    ],
                ],
            ],
        ];
    }

    private function unsecureUrlExample(): array
    {
        return [
            'http://example.com/' => [
                'original_url' => 'http://example.com/',
                'type' => 'upstream',
                'ssi' => [
                    'enabled' => false,
                ],
                'upstream' => 'mymagento',
                'cache' => [
                    'cookies' => ['*'],
                    'default_ttl' => 0,
                    'enabled' => true,
                    'headers' => [
                        'Accept',
                        'Accept-Language',
                    ],
                ],
            ],
        ];
    }

    public function getUrlsDataProvider(): array
    {
        return [
            [
                'routes' => [
                    'http://example.com/' => ['original_url' => 'http://example.com/', 'type' => 'upstream'],
                    'https://example.com/' => ['original_url' => 'https://example.com/', 'type' => 'upstream'],
                    'http://*.example.com/' => ['original_url' => 'http://*.example.com/', 'type' => 'upstream'],
                    'https://*.example.com/' => ['original_url' => 'https://*.example.com/', 'type' => 'upstream'],
                    'http://french.example.com/' => [
                        'original_url' => 'http://french.example.com/',
                        'type' => 'upstream',
                    ],
                    'https://french.example.com/' => [
                        'original_url' => 'https://french.example.com/',
                        'type' => 'upstream',
                    ],
                ],
                'expectedResult' => [
                    'secure' => [
                        'example.com' => 'https://example.com/',
                        '*.example.com' => 'https://*.example.com/',
                        'french.example.com' => 'https://french.example.com/',
                    ],
                    'unsecure' => [
                        'example.com' => 'http://example.com/',
                        '*.example.com' => 'http://*.example.com/',
                        'french.example.com' => 'http://french.example.com/',

                    ],
                ],
            ],
            [
                'routes' => [
                    'http://example.com/' => ['original_url' => 'http://{default}/', 'type' => 'upstream'],
                    'https://example.com/' => ['original_url' => 'https://{default}/', 'type' => 'upstream'],
                    'http://*.example.com/' => ['original_url' => 'http://*.{default}/', 'type' => 'upstream'],
                    'https://*.example.com/' => ['original_url' => 'https://*.{default}/', 'type' => 'upstream'],
                    'http://french.example.com/' => [
                        'original_url' => 'http://french.{default}/',
                        'type' => 'upstream',
                    ],
                    'https://french.example.com/' => [
                        'original_url' => 'https://french.{default}/',
                        'type' => 'upstream',
                    ],
                ],
                [
                    'secure' => [
                        '' => 'https://example.com/',
                        '*' => 'https://*.example.com/',
                        'french' => 'https://french.example.com/',
                    ],
                    'unsecure' => [
                        '' => 'http://example.com/',
                        '*' => 'http://*.example.com/',
                        'french' => 'http://french.example.com/',
                    ],
                ],
            ],
            'domain with www by default' => [
                'routes' => [
                    'http://example.com/' => ['original_url' => 'http://www.{default}/', 'type' => 'upstream'],
                    'https://example.com/' => ['original_url' => 'https://www.{default}/', 'type' => 'upstream'],
                    'http://*.example.com/' => ['original_url' => 'http://*.{default}/', 'type' => 'upstream'],
                    'https://*.example.com/' => ['original_url' => 'https://*.{default}/', 'type' => 'upstream'],
                    'http://french.example.com/' => [
                        'original_url' => 'http://french.{default}/',
                        'type' => 'upstream',
                    ],
                    'https://french.example.com/' => [
                        'original_url' => 'https://french.{default}/',
                        'type' => 'upstream',
                    ],
                ],
                [
                    'secure' => [
                        '' => 'https://example.com/',
                        '*' => 'https://*.example.com/',
                        'french' => 'https://french.example.com/',
                    ],
                    'unsecure' => [
                        '' => 'http://example.com/',
                        '*' => 'http://*.example.com/',
                        'french' => 'http://french.example.com/',
                    ],
                ],
            ],
        ];
    }

    public function testGetBaseUrl()
    {
        $processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn('https://example.com/');

        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('config:show:default-url')
            ->willReturn($processMock);

        $this->environmentMock->expects($this->never())
            ->method('getRoutes');

        $this->assertEquals(
            'https://example.com/',
            $this->manager->getBaseUrl()
        );
    }

    public function testExpandUrl()
    {
        $processMock = $this->createMock(ProcessInterface::class);
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn('https://example.com/');

        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('config:show:default-url')
            ->willReturn($processMock);

        $this->assertSame('https://example.com/products/123', $this->manager->expandUrl('/products/123'));
        $this->assertSame('https://example.com/products/123', $this->manager->expandUrl('products/123'));
        $this->assertSame('https://example2.com/catalog', $this->manager->expandUrl('https://example2.com/catalog'));
    }

    public function testIsRelatedDomain()
    {
        $processMock = $this->createMock(ProcessInterface::class);
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn(json_encode([
                'https://example.com/',
                'https://example2.com/',
                'https://example3.com/',
            ]));

        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('config:show:store-url')
            ->willReturn($processMock);

        $this->assertTrue($this->manager->isRelatedDomain('https://example.com/'));
        $this->assertTrue($this->manager->isRelatedDomain('https://example2.com'));
        $this->assertTrue($this->manager->isRelatedDomain('http://example3.com/'));
        $this->assertTrue($this->manager->isRelatedDomain('http://example.com/some/extra/path'));
        $this->assertFalse($this->manager->isRelatedDomain('https://example4.com'));
    }

    public function testIsUrlValid()
    {
        $processMock = $this->createMock(ProcessInterface::class);
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn(json_encode([
                'https://example.com/',
                'https://example2.com/',
                'https://example3.com/',
            ]));

        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('config:show:store-url')
            ->willReturn($processMock);

        $this->assertTrue($this->manager->isUrlValid('https://example.com/'));
        $this->assertTrue($this->manager->isUrlValid('http://example2.com'));
        $this->assertTrue($this->manager->isUrlValid('https://example.com/some/extra/path'));
        $this->assertTrue($this->manager->isUrlValid('relative/path/name'));
        $this->assertTrue($this->manager->isUrlValid('/rooted/relative/path'));
        $this->assertFalse($this->manager->isUrlValid('http://example4.com'));
        $this->assertFalse($this->manager->isUrlValid('https://example4.com/some/more/path'));
    }

    public function testGetBaseUrlWithEmptyStoreUrls()
    {
        $processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock->expects($this->never())
            ->method('getOutput');

        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('config:show:default-url')
            ->willThrowException(new ShellException('some error'));

        $this->environmentMock->expects($this->once())
            ->method('getRoutes')
            ->willReturn(['http://example.com/' => ['original_url' => 'https://{default}', 'type' => 'upstream']]);
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(
                'Cannot fetch base URL using the config:show:default-url command. ' .
                'Instead, using the URL from the MAGENTO_CLOUD_ROUTES variable.'
            );
        $this->loggerMock->expects($this->exactly(2))
            ->method('debug')
            ->withConsecutive(
                ['some error'],
                [$this->anything()]
            );

        $this->assertEquals(
            'https://example.com/',
            $this->manager->getBaseUrl()
        );
    }

    /**
     * @param array $routes
     * @param string $expectedUrl
     * @dataProvider getBaseUrlDataProvider
     */
    public function testGetBaseUrlWithErrorFromDefaultUrlCommand(array $routes, string $expectedUrl)
    {
        $processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock->expects($this->never())
            ->method('getOutput');
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('config:show:default-url')
            ->willThrowException(new ShellException('some error'));
        $this->environmentMock->expects($this->once())
            ->method('getRoutes')
            ->willReturn($routes);

        $this->assertEquals($expectedUrl, $this->manager->getBaseUrl());
    }

    /**
     * @return array
     */
    public function getBaseUrlDataProvider(): array
    {
        return [
            [
                [
                    'http://unsecure.com/' => ['original_url' => 'https://{all}', 'type' => 'upstream']
                ],
                'https://unsecure.com/'
            ],
            [
                [
                    'http://unsecure.com/' => ['original_url' => 'https://{all}', 'type' => 'upstream'],
                    'http://unsecure-default.com/' => ['original_url' => 'https://{default}', 'type' => 'upstream'],
                ],
                'https://unsecure-default.com/'
            ],
            [
                [
                    'https://secure.com/' => ['original_url' => 'https://{all}', 'type' => 'upstream'],
                    'http://unsecure.com/' => ['original_url' => 'https://{all}', 'type' => 'upstream'],
                    'http://unsecure-default.com/' => ['original_url' => 'https://{default}', 'type' => 'upstream'],
                ],
                'https://secure.com/'
            ],
            [
                [
                    'https://secure.com/' => ['original_url' => 'https://{all}', 'type' => 'upstream'],
                    'https://secure-default.com/' => ['original_url' => 'https://{default}', 'type' => 'upstream'],
                    'http://unsecure.com/' => ['original_url' => 'https://{all}', 'type' => 'upstream'],
                    'http://unsecure-default.com/' => ['original_url' => 'https://{default}', 'type' => 'upstream'],
                ],
                'https://secure-default.com/'
            ],
        ];
    }

    public function testGetBaseUrls()
    {
        $processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn(json_encode([
                'https://example.com/',
                'https://example2.com/',
            ]));

        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('config:show:store-url')
            ->willReturn($processMock);

        $this->assertEquals(
            [
                'https://example.com/',
                'https://example2.com/',
            ],
            $this->manager->getBaseUrls()
        );
    }
}
