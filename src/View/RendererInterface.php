<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\View;

/**
 * Template renderer.
 */
interface RendererInterface
{
    /**
     * Renders template ith given context.
     *
     * @param string $template
     * @param array $context
     * @return string
     */
    public function render(string $template, array $context = []): string;
}
