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
 * Check if the file dist/.magento.env.yaml contains a description of all variables
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
        /** @var File $file */
        $file = $this->container->get(File::class);
        $schema = $this->container->get(Schema::class)->getSchema();
        $path = '/dist/.magento.env.yaml';
        $content = $file->fileGetContents(ECE_BP . $path);
        $matches = [];

        if (!preg_match_all('|# ([A-Z_]+) |', $content, $matches)) {
            $this->fail(sprintf('Variables are not found in the file %s', $path));
        }

        $variables = array_keys($schema);
        // Remove skipped variables from the list
        $variables = array_diff($variables, $this->skipVariables);

        $diff = array_diff($variables, $matches[1]);

        if ($diff) {
            $message = 'Each new variable should be described in the sample file %s.'
                . ' Description of next variables is missed %s';

            $this->fail(sprintf($message, $path, implode(', ', $diff)));
        }
    }
}
