<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Composer\Semver\Semver;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Package\Manager;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine\ElasticSearch;
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
     * @var array
     */
    private $versionsMap = [
        [
            'packageVersion' => '~6.0',
            'esVersion' => '~6.0'
        ],
        [
            'packageVersion' => '~5.0',
            'esVersion' => '~5.0'
        ],
        [
            'packageVersion' => '~2.0',
            'esVersion' => '>= 1.0 < 3.0'
        ],
    ];

    /**
     * @param Validator\ResultFactory $resultFactory
     * @param Manager $manager
     * @param ElasticSearch $elasticSearch
     * @param LoggerInterface $logger
     */
    public function __construct(
        Validator\ResultFactory $resultFactory,
        Manager $manager,
        ElasticSearch $elasticSearch,
        LoggerInterface $logger
    ) {
        $this->resultFactory = $resultFactory;
        $this->manager = $manager;
        $this->elasticSearch = $elasticSearch;
        $this->logger = $logger;
    }

    /**
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        $esVersion = $this->elasticSearch->getVersion();
        if ($esVersion === '0') {
            return $this->resultFactory->success();
        }

        try {
            $esPackageVersion = $this->manager->get('elasticsearch/elasticsearch')->getVersion();

            foreach ($this->versionsMap as $versionInfo) {
                if (Semver::satisfies($esPackageVersion, $versionInfo['packageVersion'])
                    && !Semver::satisfies($esVersion, $versionInfo['esVersion'])
                ) {
                    return $this->resultFactory->error(
                        'Elasticsearch version is not compatible with current version of magento',
                        'Upgrade elasticsearch version to ' . $versionInfo['esVersion']
                    );
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Can\'t validate version of elasticsearch: ' . $e->getMessage());
        }

        return $this->resultFactory->success();
    }
}
