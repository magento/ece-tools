<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class MagentoEnvYamlTest extends TestCase
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * The list of variables which are not to be checked
     *
     * @var array
     */
    private $skipVariables = [
        StageConfigInterface::VAR_DEPLOY_FROM_GIT_OPTIONS,
        StageConfigInterface::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT,
    ];

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->container = Bootstrap::getInstance()
            ->createApplication()
            ->getContainer();
    }

    /**
     * Check if the file dist/.magento.env.yaml contains a description of all variables
     */
    public function testCheckVariables()
    {
        /** @var Schema $schema */
        $schema = $this->container->get(Schema::class);
        /** @var File $file */
        $file = $this->container->get(File::class);
        $path = '/dist/.magento.env.yaml';
        $content = $file->fileGetContents(ECE_BP . $path);
        $forgottenVariables = [];

        foreach (array_keys($schema->getSchema()) as $variable) {
            if (in_array($variable, $this->skipVariables)) {
                continue;
            }

            if (!preg_match('|# ' . $variable .'|', $content)) {
                $forgottenVariables[] = $variable;
            }
        }

        if ($forgottenVariables) {
            $message = 'Each new variable should be described in the sample file %s.'
                . ' Description of next variables is missed %s';
            $this->fail(
                sprintf($message, $path, implode(', ', $forgottenVariables)));
        }
    }
}
