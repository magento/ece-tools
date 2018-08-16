<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Composer\Semver\Semver;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\Manager;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine\ElasticSearch;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine\Config as SearchEngineConfig;
use Psr\Log\LoggerInterface;

/**
 * Validates compatibility of elasticsearch and magento versions.
 */
class ElasticSearchVersion implements ValidatorInterface
{
    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var ElasticSearch
     */
    private $elasticSearch;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SearchEngineConfig
     */
    private $searchEngineConfig;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var array
     */
    private $versionsMap = [
        [
            'packageVersion' => '~6.0',
            'esVersion' => '~6.0',
            'esVersionRaw' => '6.x',
        ],
        [
            'packageVersion' => '~5.0',
            'esVersion' => '~5.0',
            'esVersionRaw' => '5.x',
        ],
        [
            'packageVersion' => '~2.0',
            'esVersion' => '>= 1.0 < 3.0',
            'esVersionRaw' => '1.x or 2.x',
        ],
    ];

    /**
     * @param Validator\ResultFactory $resultFactory
     * @param Manager $manager
     * @param ElasticSearch $elasticSearch
     * @param LoggerInterface $logger
     * @param SearchEngineConfig $searchEngineConfig
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(
        Validator\ResultFactory $resultFactory,
        Manager $manager,
        ElasticSearch $elasticSearch,
        LoggerInterface $logger,
        SearchEngineConfig $searchEngineConfig,
        MagentoVersion $magentoVersion
    ) {
        $this->resultFactory = $resultFactory;
        $this->manager = $manager;
        $this->elasticSearch = $elasticSearch;
        $this->logger = $logger;
        $this->searchEngineConfig = $searchEngineConfig;
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * Performs compatibility validation of elasticsearch service and elasticsearch/elasticsearch package
     * according to version mapping.
     * Skips validation if elasticsearch service is not installed or another search engine configured.
     *
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        $esServiceVersion = $this->elasticSearch->getVersion();
        if ($esServiceVersion === '0') {
            return $this->resultFactory->success();
        }

        $searchEngine = $this->searchEngineConfig->get()['engine'];
        if (strpos($searchEngine, 'elasticsearch') !== 0) {
            return $this->resultFactory->success();
        }

        try {
            $esPackageVersion = $this->manager->get('elasticsearch/elasticsearch')->getVersion();

            foreach ($this->versionsMap as $versionInfo) {
                if (Semver::satisfies($esPackageVersion, $versionInfo['packageVersion'])
                    && !Semver::satisfies($esServiceVersion, $versionInfo['esVersion'])
                ) {
                    return $this->generateError($esServiceVersion, $esPackageVersion, $versionInfo);
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Can\'t validate version of elasticsearch: ' . $e->getMessage());
        }

        return $this->resultFactory->success();
    }

    /**
     * @param string $esServiceVersion
     * @param string $esPackageVersion
     * @param array $versionInfo
     * @return Validator\Result\Error
     * @throws UndefinedPackageException
     */
    private function generateError(
        string $esServiceVersion,
        string $esPackageVersion,
        array $versionInfo
    ): Validator\Result\Error {
        $error = sprintf(
            'Elasticsearch service version %s on infrastructure layer is not compatible with current version of ' .
            'elasticsearch/elasticsearch module (%s), used by your Magento application.',
            $esServiceVersion,
            $esPackageVersion
        );

        if (Semver::satisfies($esPackageVersion, '~5.0')
            && Semver::satisfies($esServiceVersion, '>= 1.0 < 3.0')
            && Semver::satisfies($this->magentoVersion->getVersion(), '~2.2.3')
        ) {
            $suggestion = ['Use one of the following methods to fix this issue:'];
            $suggestion[] = sprintf(
                '  Upgrade the Elasticsearch service on your Magento Cloud infrastructure to version %s (preferred).',
                $versionInfo['esVersionRaw']
            );
            $suggestion[] = '  Update the composer.json file for your Magento Cloud project to ' .
                'require elasticsearch/elasticsearch module version ~2.0.';
        } else {
            $suggestion = [
                sprintf(
                    'You can fix this issue by upgrading the Elasticsearch service on your ' .
                    'Magento Cloud infrastructure to version %s.',
                    $versionInfo['esVersionRaw']
                )
            ];
        }

        return $this->resultFactory->error($error, implode(PHP_EOL, $suggestion));
    }
}
