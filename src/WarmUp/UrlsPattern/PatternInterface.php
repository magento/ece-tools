<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\WarmUp\UrlsPattern;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Shell\ShellException;

/**
 * Interface
 */
interface PatternInterface
{
    /**
     * Returns list of argument based on given parameters.
     *
     * @param string $entity Entity name
     * @param string $pattern Filtering pattern which is applied for url list
     *                        Has different format for different entities.
     * @param string $storeIds Store ids/codes divided by pipe or "*" for all stores
     * @return array List of urls
     * @throws ShellException If command was executed with error
     * @throws GenericException If command result was parsed with error
     */
    public function getUrls(string $entity, string $pattern, string $storeIds): array;
}
