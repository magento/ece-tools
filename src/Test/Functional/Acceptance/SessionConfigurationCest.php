<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * This scenario checks that session can be configured through environment variable SESSION_CONFIGURATION
 * Zephyr ID MAGECLOUD-46
 *
 * @group php83
 */
class SessionConfigurationCest extends AbstractCest
{
    /**
     * @var string
     */
    protected $magentoCloudTemplate = '2.4.7-beta-test';

    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider sessionConfigurationDataProvider
     */
    public function sessionConfiguration(\CliTester $I, \Codeception\Example $data): void
    {
        $I->generateDockerCompose(
            sprintf(
                '--mode=production --env-vars="%s"',
                $this->convertEnvFromArrayToJson($data['variables'])
            )
        );
        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');

        $file = $I->grabFileContent('/app/etc/env.php');
        $I->assertStringContainsString($data['mergedConfig'], $file);
        $I->assertStringContainsString($data['defaultConfig'], $file);
    }

    /**
     * @return array
     */
    protected function sessionConfigurationDataProvider(): array
    {
        return [
            'singleConfig' => [
                'variables' => [
                    'MAGENTO_CLOUD_VARIABLES' => [
                        'SESSION_CONFIGURATION'=>['max_concurrency' => '10', '_merge' => true],
                    ],
                ],
                'mergedConfig' => 'max_concurrency',
                'defaultConfig' => 'redis',
            ],
            'withoutMerge' => [
                'variables' => [
                    'MAGENTO_CLOUD_VARIABLES' => [
                        'SESSION_CONFIGURATION'=>[
                            'save' => 'redis',
                            'redis' => [
                              'host' => 'redis.internal',
                              'port' => '6379',
                              'database' => 0,
                              'disable_locking' => 1,
                              'max_concurrency' => 10,
                              'bot_first_lifetime' => 100,
                              'bot_lifetime' => 10000,
                              'min_lifetime' => 100,
                              'max_lifetime' => 10000,
                            ],
                          ],
                        ]
                    ],
                'mergedConfig' => 'max_concurrency',
                'defaultConfig' => 'redis',
            ],
        ];
    }
}
