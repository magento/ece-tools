<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorException;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Service\ElasticSearch;
use Magento\MagentoCloud\Service\OpenSearch;

/**
 * Verifies if OpenSearch and ElasticSearch services both are installed
 */
class OpenSearchElasticSearchBothInstalled implements ValidatorInterface
{
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
     * @param Validator\ResultFactory $resultFactory
     * @param ElasticSearch $elasticSearch
     * @param OpenSearch $openSearch
     */
    public function __construct(
        Validator\ResultFactory $resultFactory,
        ElasticSearch $elasticSearch,
        OpenSearch $openSearch
    ) {
        $this->resultFactory = $resultFactory;
        $this->elasticsearch = $elasticSearch;
        $this->openSearch = $openSearch;
    }

    /**
     * @inheritDoc
     */
    public function validate(): Validator\ResultInterface
    {
        try {
            if ($this->elasticsearch->isInstalled() && $this->openSearch->isInstalled()
            ) {
                return $this->resultFactory->errorByCode(Error::WARN_OS_ES_SERVICES_BOTH_INSTALLED);
            }
        } catch (FileSystemException $exception) {
            throw new ValidatorException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $this->resultFactory->success();
    }
}
