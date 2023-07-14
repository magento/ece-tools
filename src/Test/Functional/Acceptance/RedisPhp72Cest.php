<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * @group php72
 */
class RedisPhp72Cest extends RedisCest
{
    /**
     * @return array
     */
    protected function defaultConfigurationDataProvider(): array
    {
        return [
            [
                'version' => '2.3.0',
            ],
            [
                'version' => '2.3.1',
            ],
            [
                'version' => '2.3.2',
            ],
        ];
    }

    /**
     * @return array
     */
    protected function wrongConfigurationRedisBackendDataProvider(): array
    {
        return [
            [
                'version' => '2.3.0',
                'wrongConfiguration' => [
                    'stage' => [
                        'deploy' => [
                            'REDIS_BACKEND' => 'TestRedisModel'
                        ]
                    ]
                ],
                'buildSuccess' => false,
                'deploySuccess' => false,
                'errorBuildMessage' => 'The REDIS_BACKEND variable contains an invalid value TestRedisModel.'
                    . ' Use one of the available value options: Cm_Cache_Backend_Redis,'
                    . ' \Magento\Framework\Cache\Backend\Redis,'
                    . ' \Magento\Framework\Cache\Backend\RemoteSynchronizedCache.',
                'errorDeployMessage' => '',
            ],
            [
                'version' => '2.3.2',
                'wrongConfiguration' => [
                    'stage' => [
                        'deploy' => [
                            'REDIS_BACKEND' => 'TestRedisModel'
                        ]
                    ]
                ],
                'buildSuccess' => false,
                'deploySuccess' => false,
                'errorBuildMessage' => 'The REDIS_BACKEND variable contains an invalid value TestRedisModel.'
                    . ' Use one of the available value options: Cm_Cache_Backend_Redis,'
                    . ' \Magento\Framework\Cache\Backend\Redis,'
                    . ' \Magento\Framework\Cache\Backend\RemoteSynchronizedCache.',
                'errorDeployMessage' => '',
            ],
        ];
    }

    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @skip
     */
    public function testGoodConfiguration(\CliTester $I, \Codeception\Example $data): void
    {
        return;
    }

    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @skip
     */
    public function testRedisWrongConnection(\CliTester $I, \Codeception\Example $data): void
    {
        return;
    }

    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @skip
     */
    public function testWrongConfigurationRedisBackend(\CliTester $I, \Codeception\Example $data): void
    {
        return;
    }
}
