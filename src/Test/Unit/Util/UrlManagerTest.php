<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Util\UrlManager;
use Monolog\Logger;
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
     * @var LoggerInterface
     */
    private $loggerMock;

    /**
     * @var Environment
     */
    private $environmentMock;
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->environmentMock = $this->createMock(Environment::class);

        $this->manager = new UrlManager(
            $this->environmentMock,
            $this->loggerMock
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
    public function testGetunsecureUrlMethod(array $unsecureRoute, string $expectedUrl)
    {
        $this->environmentMock->expects($this->once())
            ->method('getRoutes')
            ->willReturn($unsecureRoute);

        $urls = $this->manager->getunsecureUrls();

        $this->assertArrayHasKey($expectedUrl, $urls);
    }


    /**
     * @param array $secureRoute
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
     * @param string $expectedUrl
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
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Expected at least one valid unsecure or secure route. None found.
     */
    public function testGetUrlsException()
    {
        // No Mock so we get an exception indicating no URLS present.
        $this->manager->getUrls();
    }

    /**************** DATA PROVIDERS ***********/
    public function allRoutesDataProvider() : array
    {
        return [
            [
                $this->secureUrlExample(),
                $this->unsecureUrlExample(),
            ]
        ];
    }

    public function noSecureRouteUrlDataProvider() : array
    {
        return [
            [
                $this->unsecureUrlExample(),
                [
                    'example.com' => 'https://example.com/'
                ]
            ]
        ];
    }

    public function secureRouteDataProvider() : array
    {
        return [
            [
                $this->secureUrlExample(),
                'example.com'
            ]
        ];
    }

    public function unsecureRouteDataProvider() : array
    {
        return [
            [
                $this->unsecureUrlExample(),
                'example.com'
            ]
        ];
    }


    public function secureRouteUrlDataProvider() : array
    {
        return [
            [
                $this->secureUrlExample()
            ]
        ];
    }

    public function unsecureRouteUrlDataProvider() : array
    {
        return [
            $this->secureUrlExample()
        ];
    }

    private function secureUrlExample() : array
    {
        return [
            'https://example.com/' => [
                'original_url' => 'https://example.com/',
                'type' => 'upstream',
                'ssi' => [
                    'enabled' => false
                ],
                'upstream' => 'mymagento',
                'cache' => [
                    'cookies' => ['*'],
                    'default_ttl' => 0,
                    'enabled' => true,
                    'headers' => [
                        'Accept',
                        'Accept-Language'
                    ]
                ]
            ]
        ];
    }

    private function unsecureUrlExample() : array
    {
        return [
            'http://example.com/' => [
                'original_url' => 'https://example.com/',
                'type' => 'upstream',
                'ssi' => [
                    'enabled' => false
                ],
                'upstream' => 'mymagento',
                'cache' => [
                    'cookies' => ['*'],
                    'default_ttl' => 0,
                    'enabled' => true,
                    'headers' => [
                        'Accept',
                        'Accept-Language'
                    ]
                ]
            ]
        ];
    }
}