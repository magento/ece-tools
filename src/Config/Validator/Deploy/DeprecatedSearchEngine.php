<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\SearchEngine;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * Validates if a deprecated MySQL search engine is used.
 */
class DeprecatedSearchEngine implements ValidatorInterface
{
    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var SearchEngine
     */
    private $searchEngine;

    /**
     * @param Validator\ResultFactory $resultFactory
     * @param SearchEngine $searchEngine
     */
    public function __construct(Validator\ResultFactory $resultFactory, SearchEngine $searchEngine)
    {
        $this->resultFactory = $resultFactory;
        $this->searchEngine = $searchEngine;
    }

    /**
     * @inheritDoc
     */
    public function validate(): Validator\ResultInterface
    {
        if (SearchEngine::ENGINE_MYSQL === $this->searchEngine->getName()) {
            return $this->resultFactory->error(
                'The MySQL search configuration option is deprecated. Use Elasticsearch instead.',
                '',
                Error::WARN_DEPRECATED_MYSQL_SEARCH_ENGINE
            );
        }

        return $this->resultFactory->success();
    }
}
