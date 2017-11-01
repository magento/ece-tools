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


        $this->assertArrayHasKey('secure', $this->manager->parseRoutes($this->environmentMock->getRoutes()));
    }

    /**
     * @param array $routes
     * @dataProvider secureRouteDataProvider
     */
    public function testParseRoutesUnsecure(array $routes)
    {
        $this->environmentMock->expects($this->once())
            ->method('getRoutes')
            ->willReturn($routes);

        $urls = $this->manager->parseRoutes($this->environmentMock->getRoutes());

        $this->assertArrayHasKey('unsecure', $urls);
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

        $urls = $this->manager->getSecureUrls();

        $this->assertArrayHasKey($expectedUrl, $urls);
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
     * @dataProvider unsecureRouteUrlDataProvider
     */
    public function testNoSecure(array $unsecureRoute)
    {
        // Will be invalidated quickly
        $this->environmentMock->expects($this->once())
            ->method('getRoutes')
            ->willReturn($unsecureRoute);

        $urls = $this->manager->getUrls();

        $this->assertEquals($urls['secure'], $urls['unsecure']);
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


    public function allRoutesDataProvider()
    {
        return [
            [
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
                ],

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
            ]
        ];
    }

    public function secureRouteDataProvider()
    {
        $route = [
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
        return [
            [
                $route,
                'example.com'
            ]
        ];
    }

    public function unsecureRouteDataProvider()
    {
        $route = [
            'http://example.com/' => [
                'original_url' => 'http://example.com/',
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
        return [
            [
                $route,
                'example.com'
            ]
        ];
    }


    public function secureRouteUrlDataProvider()
    {
        return [
            [
                [
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
                ]
            ]
        ];
    }

    public function unsecureRouteUrlDataProvider()
    {

        return [
            [
                [
                    'http://example.com/' => [
                        'original_url' => 'http://example.com/',
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
                ]
                ]
        ];
    }
}
