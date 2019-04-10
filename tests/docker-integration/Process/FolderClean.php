<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\DockerIntegration\Process;

use Magento\MagentoCloud\Test\DockerIntegration\Config;

/**
 * @inheritdoc
 */
class FolderClean extends Bash
{
    /**
     * @param string|array $path
     * @param string $container
     * @param array $variables
     */
    public function __construct($path, string $container, array $variables = [])
    {
        $magentoRoot = (new Config())->get('system.magento_dir');

        if (is_array($path)) {
            $path = array_map(
                function($val) use ($magentoRoot) { return $magentoRoot . '/' . $val; },
                $path
            );
            $pathsToCleanup = implode(' ', $path);
        } else {
            $pathsToCleanup = $magentoRoot . '/' . $path;
        }

        parent::__construct('rm -rf ' . $pathsToCleanup, $container, $variables);
    }
}
