<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\PostDeploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;

/**
 * @inheritdoc
 */
class CleanCache implements ProcessInterface
{
    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @param ShellInterface $shell
     * @param Environment $environment
     */
    public function __construct(ShellInterface $shell, Environment $environment)
    {
        $this->shell = $shell;
        $this->environment = $environment;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->shell->execute('php ./bin/magento cache:flush' . $this->environment->getVerbosityLevel());
    }
}
