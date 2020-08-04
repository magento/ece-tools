<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Composer\Semver\Comparator;
use Composer\Semver\Semver;
use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\SearchEngine;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorException;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\Manager;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Service\ElasticSearch;
use Magento\MagentoCloud\Service\ServiceException;
use Psr\Log\LoggerInterface;

/**
 * Validates compatibility of elasticsearch and magento versions.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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
     * @var SearchEngine
     */
    private $searchEngine;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var array
     */
    private static $versionsMap = [
        [
            'packageVersion' => '~2.0',
            'esVersion' => '>= 1.0 < 3.0',
            'esVersionRaw' => '1.x or 2.x',
        ],
        [
            'packageVersion' => '~5.0',
            'esVersion' => '~5.0',
            'esVersionRaw' => '5.x',
        ],
        [
            'packageVersion' => '~6.0',
            'esVersion' => '~6.0',
            'esVersionRaw' => '6.x',
        ],
        [
            'packageVersion' => '>=7.5',
            'esVersion' => '>=7.5',
            'esVersionRaw' => '>=7.5',
        ],
    ];

    /**
     * @param Validator\ResultFactory $resultFactory
     * @param Manager $manager
     * @param ElasticSearch $elasticSearch
     * @param LoggerInterface $logger
     * @param SearchEngine $searchEngine
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(
        Validator\ResultFactory $resultFactory,
        Manager $manager,
        ElasticSearch $elasticSearch,
        LoggerInterface $logger,
        SearchEngine $searchEngine,
        MagentoVersion $magentoVersion
    ) {
        $this->resultFactory = $resultFactory;
        $this->manager = $manager;
        $this->elasticSearch = $elasticSearch;
        $this->logger = $logger;
        $this->searchEngine = $searchEngine;
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * Performs compatibility validation of elasticsearch service and elasticsearch/elasticsearch package
     * according to version mapping.
     * Skips validation if elasticsearch service is not installed or another search engine configured.
     *
     * {@inheritDoc}
     */
    public function validate(): Validator\ResultInterface
    {
        if (!$this->elasticSearch->isInstalled()) {
            return $this->resultFactory->success();
        }

        try {
            $esServiceVersion = $this->elasticSearch->getVersion();
        } catch (ServiceException $e) {
            throw new ValidatorException($e->getMessage(), $e->getCode(), $e);
        }

        if (!$this->searchEngine->isESFamily()) {
            return $this->resultFactory->success();
        }

        try {
            $esPackageVersion = $this->manager->get('elasticsearch/elasticsearch')->getVersion();

            foreach (self::$versionsMap as $versionInfo) {
                if (Semver::satisfies($esPackageVersion, $versionInfo['packageVersion'])
                    && !Semver::satisfies($esServiceVersion, $versionInfo['esVersion'])
                ) {
                    return $this->generateError($esServiceVersion, $esPackageVersion, $versionInfo);
                }
            }
        } catch (UndefinedPackageException $e) {
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
            $mode = Comparator::greaterThan($esServiceVersion, $esPackageVersion)
                ? 'downgrading'
                : 'upgrading';

            $suggestion = [
                sprintf(
                    'You can fix this issue by %s the Elasticsearch service on your ' .
                    'Magento Cloud infrastructure to version %s.',
                    $mode,
                    $versionInfo['esVersionRaw']
                )
            ];
        }

        return $this->resultFactory->error($error, implode(PHP_EOL, $suggestion), Error::WARN_ES_VERSION_MISMATCH);
    }
}
