<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Patch\Manager;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellException;

/**
 * @inheritdoc
 */
class ApplyPatches implements ProcessInterface
{
    /**
     * @var Manager
     */
    private $manager;

    /**
     * @param Manager $manager
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $this->manager->apply();
        } catch (ShellException $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
