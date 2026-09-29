<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * This test runs on the latest version of PHP
 *
 * @group php85
 */
class OpenSearch85Cest extends OpenSearchCest
{
        use Connect20ComposerSetupTrait;

    /**
     * Data provider for Magento Cloud versions.
     *
     * @var string
     */
    protected string $magentoCloudTemplate = '2.4.9';

    /**
     * Magento versions covered by this Cest. Add a version here to run the OpenSearch
     * wiring check against it as well.
     */
    private const SUPPORTED_VERSIONS = ['2.4.9', '2.4.10-alpha'];

    /**
     * Runs the parent's OpenSearch scenario (search engine wiring in env.php) against every
     * supported Magento version. The expected result is engine/host/port only, which is the
     * same regardless of the OpenSearch major version the template ships, so it is reused as-is.
     *
     * @return array
     */
    protected function dataProvider(): array
    {
        $expectedResult = parent::dataProvider()[0]['expectedResult'];

        $data = [];
        foreach (self::SUPPORTED_VERSIONS as $version) {
            $data[] = [
                'magento' => $version,
                'expectedResult' => $expectedResult,
            ];
        }

        return $data;
    }
}
