<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use CliTester;
use Codeception\Example;
use Magento\CloudDocker\Test\Functional\Codeception\Docker;
use Robo\Exception\TaskException;
use Magento\MagentoCloud\App\Error;

/**
 * Checks MariaDB version validation
 */
abstract class MariaDbVersionCest extends AbstractCest
{
    /**
     * @inheritdoc
     */
    public function _before(CliTester $I): void
    {
        // Do nothing. Each test case prepares the workplace using data provider template version.
    }

    /**
     * Validates MariaDB version compatibility during deploy.
     *
     * @param CliTester $I
     * @param Example $data
     * @return void
     * @throws TaskException
     * @dataProvider mariaDbVersionDataProvider
     */
    public function testMariaDbVersionValidation(CliTester $I, Example $data): void
    {
        $this->prepareWorkplace($I, $data['magentoCloudTemplate']);
        
        $this->changeMariaDbVersion($I, (string)$data['mariaDbVersion']);

        $I->generateDockerCompose(
            sprintf(
                '--mode=production --expose-db-port=%s',
                $I->getExposedPort()
            )
        );
        $this->removeVendorVolumeMountFromDockerCompose($I);

        $buildResult = $I->runDockerComposeCommand('run build cloud-build');
        if ($data['expectedSuccess']) {
            $I->assertTrue($buildResult, 'Build phase failed for supported MariaDB version ' . $data['mariaDbVersion']);
            $I->startEnvironment();
            // Apply test-only bypass after build so composer install is not affected.
            $this->applySqlVersionProviderBypass(
                $I,
                (string)$data['magentoCloudTemplate'],
                (string)$data['mariaDbVersion']
            );
            $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'), 'Deploy phase failed');
            $I->doNotSeeInOutput('errorCode: ' . Error::WARN_SERVICE_VERSION_NOT_COMPATIBLE);
        } else {
            $I->startEnvironment();
            $deployResult = $I->runDockerComposeCommand('run deploy cloud-deploy');
            $I->assertFalse(
                $deployResult,
                'Deploy phase should have failed for unsupported MariaDB version ' . $data['mariaDbVersion']
            );
            $errorLog = $I->grabFileContent('/var/log/cloud.error.log');
            $I->assertStringContainsString((string)Error::WARN_SERVICE_VERSION_NOT_COMPATIBLE, $errorLog);
        }
    }

    /**
     * Updates the project MariaDB service version in services config.
     *
     * @param CliTester $I
     * @param string $version
     * @return void
     */
    protected function changeMariaDbVersion(CliTester $I, string $version): void
    {
        $services = $I->readServicesYaml();
        $isChanged = false;

        foreach ($services as $name => &$service) {
            if (!isset($service['type'])) {
                continue;
            }

            if (preg_match('/^(mariadb|mysql):/', $service['type'])) {
                $newType = 'mariadb:' . $version;
                if ($service['type'] !== $newType) {
                    $service['type'] = $newType;
                    $isChanged = true;
                }
            }
        }

        if ($isChanged) {
            $I->writeServicesYaml($services);
        }
    }

    /**
     * Applies temporary Magento DB version validator bypass for targeted MariaDB compatibility tests.
     *
     * @param CliTester $I
     * @param string $magentoCloudTemplate
     * @param string $mariaDbVersion
     * @return void
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function applySqlVersionProviderBypass(
        CliTester $I,
        string $magentoCloudTemplate,
        string $mariaDbVersion
    ): void {
        $supportedTemplates = ['2.4.7', '2.4.8', '2.4.9'];
        if (!in_array($magentoCloudTemplate, $supportedTemplates, true)) {
            return;
        }

        $dbPatternName = '';
        $dbPatternRegex = '';
        $providerRegex = '';
        $providerVersion = '';

        if (str_starts_with($mariaDbVersion, '11.8')) {
            $dbPatternName = 'MariaDB-11.8';
            $dbPatternRegex = '^11\.8\.';
            $providerRegex = '^11\\\\.8\\\\.';
            $providerVersion = '11.8.';
        } elseif (str_starts_with($mariaDbVersion, '12.2')) {
            $dbPatternName = 'MariaDB-12.2';
            $dbPatternRegex = '^12\.2\.';
            $providerRegex = '^12\\\\.2\\\\.';
            $providerVersion = '12.2.';
        } elseif (str_starts_with($mariaDbVersion, '12.3')) {
            $dbPatternName = 'MariaDB-12.3';
            $dbPatternRegex = '^12\.3\.';
            $providerRegex = '^12\\\\.3\\\\.';
            $providerVersion = '12.3.';
        } else {
            return;
        }

        $workDir = $I->getWorkDirPath();
        $diXmlPaths = [
            $workDir . '/app/etc/di.xml',
            $workDir . '/vendor/magento/magento2-base/app/etc/di.xml',
        ];
        $patchedDiXml = false;
        $newPatternLine = sprintf(
            '<item name="%s" xsi:type="string">%s</item>',
            $dbPatternName,
            $dbPatternRegex
        );

        foreach ($diXmlPaths as $diXmlPath) {
            if (!is_file($diXmlPath)) {
                continue;
            }

            $content = file_get_contents($diXmlPath);
            $I->assertNotFalse($content, sprintf('Cannot read DI config file: %s', $diXmlPath));

            if (str_contains($content, sprintf('name="%s"', $dbPatternName))) {
                $patchedDiXml = true;
                continue;
            }

            $diPatternInjected = false;
            $updated = preg_replace_callback(
                '/(<argument name="dbVersionPatterns" xsi:type="array">)(.*?)(<\/argument>)/s',
                static function (array $matches) use ($newPatternLine, &$diPatternInjected): string {
                    $body = $matches[2];

                    if (!preg_match_all(
                        '/^([ \t]*)<item name="MariaDB-[^"]+" xsi:type="string">[^<]+<\/item>\s*$/m',
                        $body,
                        $mariaDbItems,
                        PREG_OFFSET_CAPTURE
                    )) {
                        return $matches[0];
                    }

                    $lastItemIndex = count($mariaDbItems[0]) - 1;
                    $lastItem = $mariaDbItems[0][$lastItemIndex];
                    $indentation = $mariaDbItems[1][$lastItemIndex][0] ?: '                ';
                    $injectedItem = $lastItem[0] . PHP_EOL . $indentation . $newPatternLine;
                    $newBody = substr_replace($body, $injectedItem, $lastItem[1], strlen($lastItem[0]));

                    $diPatternInjected = true;

                    return $matches[1] . $newBody . $matches[3];
                },
                $content,
                1
            );

            if ($diPatternInjected) {
                $I->assertNotFalse(
                    file_put_contents($diXmlPath, $updated),
                    sprintf('Cannot write updated DI config file: %s', $diXmlPath)
                );
                $patchedDiXml = true;
            }
        }

        // Some Magento templates do not expose dbVersionPatterns in di.xml anymore.
        // In that case we continue with the SqlVersionProvider fallback patch below.
        if (!$patchedDiXml) {
            $I->comment(sprintf(
                'Skipping %s DI config injection: dbVersionPatterns argument not found in checked di.xml files.',
                $dbPatternName
            ));
        }

        $installCommandFactoryPath = $I->getWorkDirPath()
            . '/vendor/magento/ece-tools/src/Step/Deploy/InstallUpdate/Install/Setup/InstallCommandFactory.php';
        $I->assertTrue(
            is_file($installCommandFactoryPath),
            sprintf('Cannot find InstallCommandFactory file: %s', $installCommandFactoryPath)
        );

        $installFactoryContent = file_get_contents($installCommandFactoryPath);
        $I->assertNotFalse(
            $installFactoryContent,
            sprintf('Cannot read InstallCommandFactory file: %s', $installCommandFactoryPath)
        );

        if (!str_contains($installFactoryContent, "'--skip-db-validation' => null")) {
            $installFactoryUpdated = str_replace(
                "'--cleanup-database' => null,",
                "'--cleanup-database' => null,\n            '--skip-db-validation' => null,",
                $installFactoryContent,
                $factoryReplacementCount
            );
            $I->assertSame(
                1,
                $factoryReplacementCount,
                'Cannot inject --skip-db-validation option into InstallCommandFactory.'
            );
            $I->assertNotFalse(
                file_put_contents($installCommandFactoryPath, $installFactoryUpdated),
                sprintf('Cannot write InstallCommandFactory file: %s', $installCommandFactoryPath)
            );
        }

        $sqlVersionProviderPath = $workDir . '/vendor/magento/framework/DB/Adapter/SqlVersionProvider.php';
        $I->assertTrue(
            is_file($sqlVersionProviderPath),
            sprintf('Cannot find SqlVersionProvider file: %s', $sqlVersionProviderPath)
        );
        $sqlVersionProviderContent = file_get_contents($sqlVersionProviderPath);
        $I->assertNotFalse(
            $sqlVersionProviderContent,
            sprintf('Cannot read SqlVersionProvider file: %s', $sqlVersionProviderPath)
        );

        if (!str_contains($sqlVersionProviderContent, "preg_match('/{$providerRegex}'")) {
            $fallbackNeedle = '        if (empty($match)) {';
            $fallbackReplacement = "        if (empty(\$match)"
                . " && preg_match('/{$providerRegex}/', \$sqlVersionOutput)) {\n"
                . "            return '{$providerVersion}';\n"
                . "        }\n"
                . "        if (empty(\$match)) {";
            $sqlVersionProviderUpdated = str_replace(
                $fallbackNeedle,
                $fallbackReplacement,
                $sqlVersionProviderContent,
                $sqlProviderReplacementCount
            );
            $I->assertSame(
                1,
                $sqlProviderReplacementCount,
                sprintf('Cannot inject %s fallback into SqlVersionProvider.', $dbPatternName)
            );
            $I->assertNotFalse(
                file_put_contents($sqlVersionProviderPath, $sqlVersionProviderUpdated),
                sprintf('Cannot write SqlVersionProvider file: %s', $sqlVersionProviderPath)
            );
        }
    }

    /**
     * Provides MariaDB version validation scenarios.
     *
     * @return array
     */
    abstract protected function mariaDbVersionDataProvider(): array;
}
