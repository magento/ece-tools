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
class Bash extends Process
{
    /**
     * @param string $command
     * @param array $variables
     * @param string $container
     */
    public function __construct(string $command, string $container, array $variables = [])
    {
        $variables = array_replace_recursive($this->getDefaultVariables(), $variables);
        $options = '';

        foreach ($variables as $varName => $varValue) {
            $options .= sprintf(' -e %s=%s', $varName, base64_encode(json_encode($varValue)));
        }

        $command = sprintf(
            'docker-compose run%s %s bash -c "%s"',
            $options,
            $container,
            $command
        );

        parent::__construct($command);
    }

    /**
     * @return array
     */
    private function getDefaultVariables(): array
    {
        $config = new Config();

        return [
            'MAGENTO_CLOUD_RELATIONSHIPS' => [
                'database' => [
                    0 => [
                        'host' => 'db',
                        'path' => 'magento2',
                        'password' => 'magento2',
                        'username' => 'magento2',
                        'port' => '3306',
                    ],
                ],
            ],
            'MAGENTO_CLOUD_ROUTES' => [
                $config->get('env.url.base') => [
                    'type' => 'upstream',
                    'original_url' => 'http://{default}',
                ],
                $config->get('env.url.base') => [
                    'type' => 'upstream',
                    'original_url' => 'https://{default}',
                ]
            ],
            'MAGENTO_CLOUD_VARIABLES' => [
                'ADMIN_EMAIL' => 'admin@example.com',
            ],
        ];
    }
}
