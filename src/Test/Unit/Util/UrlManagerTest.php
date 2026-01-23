<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellFactory;
use Magento\MagentoCloud\Util\UrlManager;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 * @see UrlManager
 */
#[AllowMockObjectsWithoutExpectations]
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
    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
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
     * Test parsing of secure routes.
     *
     * @param array $routes
     * @dataProvider secureRouteDataProviderForParse
     */
    #[DataProvider('secureRouteDataProviderForParse')]
    public function testParseRoutesSecure(array $routes): void
    {
        $this->environmentMock->expects($this->once())
            ->method('getRoutes')
            ->willReturn($routes);

        $this->assertArrayHasKey('secure', $this->manager->getUrls());
    }

    /**
     * Data provider for testParseRoutesSecure (routes only).
     *
     * @return array
     */
    public static function secureRouteDataProviderForParse(): array
    {
        return [
            [self::secureUrlExample()],
        ];
    }

    /**
     * Test parsing of unsecure routes.
     *
     * @param array $routes
     * @dataProvider unsecureRouteDataProviderForParse
     */
    #[DataProvider('unsecureRouteDataProviderForParse')]
    public function testParseRoutesUnsecure(array $routes): void
    {
        $this->environmentMock->expects($this->once())
            ->method('getRoutes')
            ->willReturn($routes);

        $this->assertArrayHasKey('unsecure', $this->manager->getUrls());
    }

    /**
     * Data provider for testParseRoutesUnsecure (routes only).
     *
     * @return array
     */
    public static function unsecureRouteDataProviderForParse(): array
    {
        return [
            [self::unsecureUrlExample()],
        ];
    }

    /**
     * Test getting secure URLs.
     *
     * @param array $secureRoute
     * @param string $expectedUrl
     * @dataProvider secureRouteDataProvider
     */
    #[DataProvider('secureRouteDataProvider')]
    public function testGetSecureUrlMethod(array $secureRoute, string $expectedUrl): void
    {
        $this->environmentMock->expects($this->once())
            ->method('getRoutes')
            ->willReturn($secureRoute);

        $this->assertArrayHasKey($expectedUrl, $this->manager->getSecureUrls());
    }

    /**
     * Test getting unsecure URLs.
     *
     * @param array $unsecureRoute
     * @param string $expectedUrl
     * @dataProvider unsecureRouteDataProvider
     */
    #[DataProvider('unsecureRouteDataProvider')]
    public function testGetUnsecureUrlMethod(array $unsecureRoute, string $expectedUrl): void
    {
        $this->environmentMock->expects($this->once())
            ->method('getRoutes')
            ->willReturn($unsecureRoute);

        $urls = $this->manager->getUnsecureUrls();

        $this->assertArrayHasKey($expectedUrl, $urls);
    }

    /**
     * Test no secure routes present.
     *
     * @param array $unsecureRoute
     * @param $expectedUrl
     * @dataProvider noSecureRouteUrlDataProvider
     */
    #[DataProvider('noSecureRouteUrlDataProvider')]
    public function testNoSecure(array $unsecureRoute, array $expectedUrl): void
    {
        $this->environmentMock->expects($this->once())
            ->method('getRoutes')
            ->willReturn($unsecureRoute);

        $this->assertEquals($this->manager->getUrls()['secure'], $expectedUrl);
    }

    /**
     * Test getting secure URLs.
     *
     * @param array $secureRoute
     * @dataProvider secureRouteUrlDataProvider
     */
    #[DataProvider('secureRouteUrlDataProvider')]
    public function testGetSecureUrl(array $secureRoute): void
    {
        $this->environmentMock->expects($this->once())
            ->method('getRoutes')
            ->willReturn($secureRoute);
        $urls = $this->manager->getUrls();

        $this->assertEquals($urls['unsecure'], $urls['secure']);
    }

    /**
     * Test getting all URLs.
     *
     * @param array $routes
     * @param array $expectedResult
     * @dataProvider getUrlsDataProvider
     */
    #[DataProvider('getUrlsDataProvider')]
    public function testGetUrls(array $routes, array $expectedResult): void
    {
        $this->environmentMock->expects($this->once())
            ->method('getRoutes')
            ->willReturn($routes);

        $this->assertEquals($expectedResult, $this->manager->getUrls());
        // Lazy load.
        $this->assertEquals($expectedResult, $this->manager->getUrls());
    }

    /**
     * Test getting primary URLs.
     *
     * @param array $routes
     * @param array $expectedResult
     * @dataProvider getPrimaryUrlsDataProvider
     */
    #[DataProvider('getPrimaryUrlsDataProvider')]
    public function testGetPrimaryUrls(array $routes, array $expectedResult): void
    {
        $this->environmentMock->expects($this->once())
            ->method('getRoutes')
            ->willReturn($routes);

        $this->assertEquals($expectedResult, $this->manager->getUrls());
    }

    /**
     * Test getting URLs exception.
     *
     * @return void
     * @throws \RuntimeException
     */
    public function testGetUrlsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expected at least one valid unsecure or secure route. None found.');

        // No Mock so we get an exception indicating no URLs present.
        $this->manager->getUrls();
    }

    /**
     * Test all routes data provider.
     *
     * @return array
     */
    public static function allRoutesDataProvider(): array
    {
        return [
            [
                self::secureUrlExample(),
                self::unsecureUrlExample(),
            ],
        ];
    }

    /**
     * Test no secure routes present.
     *
     * @return array
     */
    public static function noSecureRouteUrlDataProvider(): array
    {
        return [
            [
                self::unsecureUrlExample(),
                [
                    'example.com' => 'https://example.com/',
                ],
            ],
        ];
    }

    /**
     * Test secure routes data provider.
     *
     * @return array
     */
    public static function secureRouteDataProvider(): array
    {
        return [
            [
                self::secureUrlExample(),
                'example.com',
            ],
        ];
    }

    /**
     * Test unsecure routes data provider.
     *
     * @return array
     */
    public static function unsecureRouteDataProvider(): array
    {
        return [
            [
                self::unsecureUrlExample(),
                'example.com',
            ],
        ];
    }

    /**
     * Test secure routes data provider.
     *
     * @return array
     */
    public static function secureRouteUrlDataProvider(): array
    {
        return [
            [
                self::secureUrlExample(),
            ],
        ];
    }

    /**
     * Test unsecure routes data provider.
     *
     * @return array
     */
    public static function unsecureRouteUrlDataProvider(): array
    {
        return [
            self::secureUrlExample(),
        ];
    }

    /**
     * Secure URL example.
     *
     * @return array
     */
    private static function secureUrlExample(): array
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

    /**
     * Unsecure URL example.
     *
     * @return array
     */
    private static function unsecureUrlExample(): array
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

    /**
     * DataProvider for testGetUrls.
     *
     * @return array
     */
    public static function getUrlsDataProvider(): array
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
                'expectedResult' => [
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
            [
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
                'expectedResult' => [
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

    /**
     * DataProvider for testGetPrimaryUrls
     *
     * @return array
     */
    public static function getPrimaryUrlsDataProvider(): array
    {
        return [
            'with unsecure primary' => [
                'routes' => [
                    'http://example.com/' => [
                        'original_url' => 'http://{default}/',
                        'type' => 'upstream',
                        'primary' => false,
                    ],
                    'http://www.custom.example.com/' => [
                        'original_url' => 'http://{all}/',
                        'type' => 'upstream',
                        'primary' => false,
                    ],
                    'http://custom.example.com/' => [
                        'original_url' => 'http://{default}/',
                        'type' => 'upstream',
                        'primary' => true,
                    ],
                    'https://french.example.com/' => [
                        'original_url' => 'https://french.{default}/',
                        'type' => 'upstream',
                        'primary' => false,
                    ],
                ],
                'expectedResult' => [
                    'secure' => [
                        '' => 'https://custom.example.com/',
                    ],
                    'unsecure' => [
                        '' => 'http://custom.example.com/',
                    ],
                ],
            ],
            'secure primary' => [
                'routes' => [
                    'http://example.com/' => [
                        'original_url' => 'http://{default}/',
                        'type' => 'upstream',
                        'primary' => false,
                    ],
                    'http://www.example.com/' => [
                        'original_url' => 'http://{all}/',
                        'type' => 'upstream',
                        'primary' => false,
                    ],
                    'https://custom.example.com/' => [
                        'original_url' => 'http://{default}/',
                        'type' => 'upstream',
                        'primary' => true,
                    ],
                ],
                'expectedResult' => [
                    'secure' => [
                        '' => 'https://custom.example.com/',
                    ],
                    'unsecure' => [
                        '' => 'https://custom.example.com/',
                    ],
                ],
            ],
            'all primary false and one secure' => [
                'routes' => [
                    'http://example.com/' => [
                        'original_url' => 'http://{default}/',
                        'type' => 'upstream',
                        'primary' => false,
                    ],
                    'http://www.example.com/' => [
                        'original_url' => 'http://{all}/',
                        'type' => 'upstream',
                        'primary' => false,
                    ],
                    'https://www.example.com/' => [
                        'original_url' => 'http://{all}/',
                        'type' => 'upstream',
                        'primary' => false,
                    ],
                ],
                'expectedResult' => [
                    'secure' => [
                        '{all}' => 'https://www.example.com/',
                    ],
                    'unsecure' => [
                        '' => 'http://example.com/',
                        '{all}' => 'http://www.example.com/',
                    ],
                ],
            ],
        ];
    }

    /**
     * Test getBaseUrl method.
     *
     * @return void
     */
    public function testGetBaseUrl(): void
    {
        $processMock = $this->createMock(ProcessInterface::class);
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

    /**
     * Test expandUrl method.
     *
     * @return void
     */
    public function testExpandUrl(): void
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

    /**
     * Test isRelatedDomain method.
     *
     * @return void
     */
    public function testIsRelatedDomain(): void
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

    /**
     * Test isUrlValid method.
     *
     * @return void
     */
    public function testIsUrlValid(): void
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

    /**
     * Test getBaseUrl method with empty store URLs.
     *
     * @return void
     */
    public function testGetBaseUrlWithEmptyStoreUrls(): void
    {
        $processMock = $this->createMock(ProcessInterface::class);
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
        $invokedCount = $this->atLeast(3);
        $this->loggerMock->expects($invokedCount)
            ->method('debug')
            ->willReturnCallback(function ($parameters) use ($invokedCount) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertSame('some error', $parameters);
                }
        
                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertSame('Initializing routes.', $parameters);
                }

                if ($invokedCount->numberOfInvocations() === 3) {
                    $this->assertThat($parameters, $this->anything());
                }
            });

        $this->assertEquals(
            'https://example.com/',
            $this->manager->getBaseUrl()
        );
    }

    /**
     * Test getBaseUrl method with error from default-url command.
     *
     * @param array $routes
     * @param string $expectedUrl
     * @dataProvider getBaseUrlDataProvider
     */
    #[DataProvider('getBaseUrlDataProvider')]
    public function testGetBaseUrlWithErrorFromDefaultUrlCommand(array $routes, string $expectedUrl): void
    {
        $processMock = $this->createMock(ProcessInterface::class);
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
     * Data provider for testGetBaseUrlWithErrorFromDefaultUrlCommand.
     *
     * @return array
     */
    public static function getBaseUrlDataProvider(): array
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

    /**
     * Test getBaseUrls method.
     *
     * @return void
     */
    public function testGetBaseUrls(): void
    {
        $processMock = $this->createMock(ProcessInterface::class);
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
