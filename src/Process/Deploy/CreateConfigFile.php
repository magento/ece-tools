<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Deploy\Writer as DeployConfigWriter;

/**
 * @inheritdoc
 */
class CreateConfigFile implements ProcessInterface
{
    /**
     * @var DeployConfigWriter
     */
    private $deploymentConfigWriter;

    /**
     * @param DeployConfigWriter $deploymentConfigWriter
     */
    public function __construct(DeployConfigWriter $deploymentConfigWriter)
    {
        $this->deploymentConfigWriter = $deploymentConfigWriter;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->deploymentConfigWriter->update([]);
    }
}
