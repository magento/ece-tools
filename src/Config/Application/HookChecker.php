<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Application;

use Magento\MagentoCloud\Command\PostDeploy;

/**
 * Checks if hooks enabled in .magento.app.yaml
 */
class HookChecker
{
    const HOOK_POST_DEPLOY = 'post_deploy';

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @param Reader $reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Checks that `ece-tool post-deploy` is added to post_deploy hook.
     *
     * @return bool
     * @throws \RuntimeException If configuration file can't be read or parse
     */
    public function isPostDeployHookEnabled(): bool
    {
        try {
            $appConfig = $this->reader->read();

            if (isset($appConfig['hooks'][self::HOOK_POST_DEPLOY])) {
                $postDeployHooks = $appConfig['hooks'][self::HOOK_POST_DEPLOY];

                if (preg_match(sprintf('/ece-tools\s+%s/', PostDeploy::NAME), $postDeployHooks)) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            throw new \RuntimeException('Error during reading application configuration file: ' . $e->getMessage());
        }

        return false;
    }
}
