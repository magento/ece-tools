<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\StaticContent;

interface OptionInterface
{
    /**
     * @return int
     */
    public function getThreadCount(): int;

    /**
     * @return string
     */
    public function getStrategy(): string;

    /**
     * Collects locales for static content deployment
     *
     * @return array List of locales.
     * ```php
     * [
     *     'en_US',
     *     'fr_FR'
     * ]
     * ```
     */
    public function getLocales(): array;

    /**
     * @return bool
     */
    public function isForce(): bool;

    /**
     * @return string
     */
    public function getVerbosityLevel(): string;

    /**
     * @return int|null
     */
    public function getMaxExecutionTime();
}
