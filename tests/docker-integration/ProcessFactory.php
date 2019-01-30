<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\DockerIntegration;

use Symfony\Component\Process\Process;

class ProcessFactory
{
    /**
     * @param string $command
     * @param mixed|string $cwd
     * @return Process
     */
    public function create(string $command, $cwd = __DIR__ . '/../..'): Process
    {
        return new Process($command, $cwd);
    }

    public function createCompose(string $command, string $container, array $variables = []): Process
    {
        $variables = array_replace_recursive($this->getDefaultEnvironmentVariables(), $variables);
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

        return $this->create($command);
    }

    private function getDefaultEnvironmentVariables(): array
    {
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
                "http://localhost:8090/" => [
                    'type' => 'upstream',
                    'original_url' => 'http://{default}',
                ],
                "https://localhost:8090/" => [
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
