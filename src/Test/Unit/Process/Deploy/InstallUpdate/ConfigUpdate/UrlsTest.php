<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Urls;
use Magento\MagentoCloud\Util\UrlManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class UrlsTest extends TestCase
{
    /**
     * @var Urls
     */
    private $process;

    /**
     * @var Environment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $environmentMock;

    /**
     * @var ConnectionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connectionMock;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var UrlManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlManagerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->connectionMock = $this->getMockBuilder(ConnectionInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->urlManagerMock = $this->getMockBuilder(UrlManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->process = new Urls(
            $this->environmentMock,
            $this->connectionMock,
            $this->loggerMock,
            $this->urlManagerMock
        );
    }

    /**
     * @inheritdoc
     */
    public function testExecute()
    {
        $this->environmentMock->method('isUpdateUrlsEnabled')
            ->willReturn(true);
        $this->loggerMock->method('info')
            ->with('Updating secure and unsecure URLs');
        $this->urlManagerMock->method('getUrls')
            ->willReturn([
                'secure' => [
                    '' => 'https://route_default.local',
                    'route_1' => 'https://route1.local',
                    'route_2' => 'https://route2.local',
                ],
                'unsecure' => [
                    'route_4' => 'http://route4.local',
                ],
            ]);
        // @codingStandardsIgnoreStart
        $this->connectionMock->method('affectingQuery')
            ->withConsecutive(
                ["UPDATE `core_config_data` SET `value` = 'https://route_default.local' WHERE `path` = 'web/secure/base_url' AND `scope_id` = '0'"],
                ["UPDATE `core_config_data` SET `value` = 'https://route1.local' WHERE `path` = 'web/secure/base_url' AND (`value` LIKE 'https://route_1%' OR `value` LIKE 'https://route_1%')"],
                ["UPDATE `core_config_data` SET `value` = 'https://route2.local' WHERE `path` = 'web/secure/base_url' AND (`value` LIKE 'https://route_2%' OR `value` LIKE 'https://route_2%')"],
                ["UPDATE `core_config_data` SET `value` = 'http://route4.local' WHERE `path` = 'web/unsecure/base_url' AND (`value` LIKE 'http://route_4%' OR `value` LIKE 'http://route_4%')"]
            );
        // @codingStandardsIgnoreEnd

        $this->process->execute();
    }

    public function testExecuteSkipped()
    {
        $this->environmentMock->method('isUpdateUrlsEnabled')
            ->willReturn(false);
        $this->loggerMock->method('notice')
            ->with('Skipping URL updates');

        $this->process->execute();
    }
}
