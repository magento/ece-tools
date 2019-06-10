<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Robo\Tasks;

use Robo\Common\ExecOneCommand;
use Robo\Contract\CommandInterface;
use Robo\Result;
use Robo\Task\BaseTask;

/**
 * Generate docker-compose.yml
 */
class GenerateDockerCompose extends BaseTask implements CommandInterface
{
    use ExecOneCommand;

    /**
     * @var array
     */
    private $services;

    /**
     * @var array
     */
    private $availableServices = ['php', 'nginx', 'db', 'es', 'redis', 'rmq', 'node'];

    /**
     * @param array $services
     * @throws \RuntimeException
     */
    public function __construct(array $services = [])
    {
        if (!isset($services['php'])) {
            $services['php'] = (float) PHP_VERSION;
        }

        $this->checkServicesAvailability($services);
        $this->services = $services;
    }

    /**
     * @param array $services
     * @throws \RuntimeException
     */
    private function checkServicesAvailability(array $services = [])
    {
        $diff = array_diff(array_keys($services), $this->availableServices);
        if ($diff) {
            throw new \RuntimeException(sprintf('These services are not available: %s', implode(', ', $diff)));
        }
    }

    /**
     * @inheritdoc
     */
    public function getCommand(): string
    {
        $command = './bin/ece-tools docker:build --mode=functional';

        foreach ($this->services as $service => $version) {
            $command .= sprintf(' --%s=%s', $service, $version);
        }

        return $command;
    }

    /**
     * @inheritdoc
     */
    public function run(): Result
    {
        return $this->executeCommand($this->getCommand());
    }
}
