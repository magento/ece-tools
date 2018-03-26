<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Stage;

use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;

/**
 * @inheritdoc
 */
class PostDeploy implements PostDeployInterface
{
    /**
     * @var EnvironmentReader
     */
    private $environmentReader;

    /**
     * @var array
     */
    private $mergedConfig;

    /**
     * @param EnvironmentReader $environmentReader
     */
    public function __construct(EnvironmentReader $environmentReader)
    {
        $this->environmentReader = $environmentReader;
    }

    /**
     * @inheritdoc
     */
    public function get(string $name)
    {
        if (!array_key_exists($name, $this->getDefault())) {
            throw new \RuntimeException(sprintf(
                'Config %s was not defined.',
                $name
            ));
        }

        try {
            return $this->mergeConfig()[$name];
        } catch (\Exception $exception) {
            throw new \RuntimeException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * @return array
     * @throws \Magento\MagentoCloud\Filesystem\FileSystemException
     */
    private function mergeConfig(): array
    {
        if (null === $this->mergedConfig) {
            $envConfig = $this->environmentReader->read()[self::SECTION_STAGE] ?? [];

            $this->mergedConfig = array_replace(
                $this->getDefault(),
                $envConfig[self::STAGE_GLOBAL] ?? [],
                $envConfig[self::STAGE_POST_DEPLOY] ?? []
            );
        }

        return $this->mergedConfig;
    }

    /**
     * Resolves default configuration value if other was not provided.
     *
     * @return array
     */
    private function getDefault(): array
    {
        return [
            self::VAR_WARM_UP_PAGES => [
                'index.php',
            ],
        ];
    }
}
