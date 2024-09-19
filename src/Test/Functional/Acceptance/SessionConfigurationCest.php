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
 */
abstract class SessionConfigurationCest extends AbstractCest
{
    /**
     * @var string
     */
    protected $magentoCloudTemplate = '2.4.7';

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
    abstract protected function sessionConfigurationDataProvider(): array;
}
