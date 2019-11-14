<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\WarmUp\UrlsPattern;

use Magento\MagentoCloud\Shell\ShellException;

/**
 * Interface
 */
interface PatternInterface
{
    /**
     * Returns list of argument based on given parameters.
     *
     * @param string $entity
     * @param string $pattern
     * @param string $storeIds
     * @return array
     * @throws ShellException If command was executed with error
     * @throws ParseResultException If command result was parsed with error
     */
    public function getUrls(string $entity, string $pattern, string $storeIds): array;
}
