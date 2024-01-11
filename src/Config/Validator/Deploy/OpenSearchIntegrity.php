<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Magento\Shared\Reader;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorException;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Service\ElasticSearch;
use Magento\MagentoCloud\Service\OpenSearch;

/**
 * Verifies if OpenSearch service present for Magento 2.4.4 and above
 */
class OpenSearchIntegrity implements ValidatorInterface
{
    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var ElasticSearch
     */
    private $elasticsearch;

    /**
     * @var OpenSearch
     */
    private $openSearch;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @param MagentoVersion $magentoVersion
     * @param Validator\ResultFactory $resultFactory
     * @param ElasticSearch $elasticSearch
     * @param OpenSearch $openSearch
     * @param Reader $reader
     */
    public function __construct(
        MagentoVersion $magentoVersion,
        Validator\ResultFactory $resultFactory,
        ElasticSearch $elasticSearch,
        OpenSearch $openSearch,
        Reader $reader
    ) {
        $this->magentoVersion = $magentoVersion;
        $this->resultFactory = $resultFactory;
        $this->elasticsearch = $elasticSearch;
        $this->openSearch = $openSearch;
        $this->reader = $reader;
    }

    /**
     * @inheritDoc
     */
    public function validate(): Validator\ResultInterface
    {
        try {
            if ($this->magentoVersion->isGreaterOrEqual('2.4.0') && $this->elasticsearch->isInstalled()) {
                return $this->resultFactory->success();
            }

            if (!$this->magentoVersion->satisfies('>=2.3.7-p3 <2.4.0 || >=2.4.3-p2')
                && $this->openSearch->isInstalled()
            ) {
                return $this->resultFactory->errorByCode(Error::DEPLOY_MAGENTO_VERSION_DOES_NOT_SUPPORT_OS);
            }

            $modules = $this->reader->read()['modules'] ?? [];
            $liveSearchEnabled = $modules['Magento_LiveSearchAdapter'] ?? false;

            if ($this->magentoVersion->isGreaterOrEqual('2.4.3-p2')
                && !$this->openSearch->isInstalled() && !$liveSearchEnabled
            ) {
                return $this->resultFactory->errorByCode(Error::DEPLOY_OS_SERVICE_NOT_INSTALLED);
            }
        } catch (UndefinedPackageException | FileSystemException $exception) {
            throw new ValidatorException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $this->resultFactory->success();
    }
}
