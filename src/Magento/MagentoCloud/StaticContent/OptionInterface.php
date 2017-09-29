<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\StaticContent;

interface OptionInterface
{
    public function getTreadCount(): int;
    public function getExcludedThemes(): array;
    public function getStrategy(): string;
    public function getLocales(): array;
    public function isForce(): bool;
    public function getVerbosityLevel(): string;
}
