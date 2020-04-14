<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Application;

use Magento\MagentoCloud\Config\Environment;

/**
 * Checks hooks enabling in .magento.app.yaml
 */
class HookChecker
{
    private const HOOK_POST_DEPLOY = 'post_deploy';

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @param Environment $environment
     */
    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * Checks that post_deploy hook is configured and `ece-tool post-deploy` is added to post_deploy hook.
     *
     * @return bool
     */
    public function isPostDeployHookEnabled(): bool
    {
        $appConfig = $this->environment->getApplication();

        $postDeployHooks = $appConfig['hooks'][self::HOOK_POST_DEPLOY] ?? '';

        return false !== strpos($postDeployHooks, '/ece-tools');
    }
}
