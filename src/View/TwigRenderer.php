<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\View;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Twig implementation of template renderer.
 */
class TwigRenderer implements RendererInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @param DirectoryList $directoryList
     */
    public function __construct(DirectoryList $directoryList)
    {
        $this->environment = new Environment(new FilesystemLoader(
            $directoryList->getViews()
        ), ['cache' => false]);
    }

    /**
     * @inheritdoc
     */
    public function render(string $template, array $context = []): string
    {
        return $this->environment->render($template, $context);
    }
}
