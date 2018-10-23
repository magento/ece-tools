<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\DB\ConnectionInterface;
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
     * @var ConnectionInterface|MockObject
     */
    private $connection;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->environmentMock = $this->createMock(Environment::class);
        $this->connection = $this->getMockForAbstractClass(ConnectionInterface::class);

        $this->manager = new UrlManager(
            $this->environmentMock,
            $this->loggerMock,
            $this->connection
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
        $this->connection->expects($this->once())
            ->method('selectOne')
            ->with(
                'SELECT `value` from `core_config_data` WHERE `path` = ? ORDER BY `config_id` ASC LIMIT 1'
            )->willReturn([
                'value' => 'https://example.com/',
            ]);

        $this->assertEquals(
            'https://example.com/',
            $this->manager->getBaseUrl()
        );
    }

    /**
     * @param array $routes
     * @dataProvider getBaseUrlPlaceholderDataProvider
     */
    public function testGetBaseUrlPlaceholder(array $routes)
    {
        $this->connection->expects($this->once())
            ->method('selectOne')
            ->with(
                'SELECT `value` from `core_config_data` WHERE `path` = ? ORDER BY `config_id` ASC LIMIT 1'
            )->willReturn([
                'value' => '{base_url}/',
            ]);
        $this->environmentMock->expects($this->once())
            ->method('getRoutes')
            ->willReturn($routes);

        $this->assertEquals(
            'https://example.com/',
            $this->manager->getBaseUrl()
        );
    }

    /**
     * @return array
     */
    public function getBaseUrlPlaceholderDataProvider(): array
    {
        return [
            [
                'routes' => [
                    'http://example.com/' => ['original_url' => 'https://{default}', 'type' => 'upstream'],
                ],
            ],
        ];
    }
}
