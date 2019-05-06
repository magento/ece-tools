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
use Magento\MagentoCloud\Test\Functional\Codeception\Docker;

/**
 * Up Docker environment
 */
class EnvUp extends BaseTask implements CommandInterface
{
    use ExecOneCommand;

    /**
     * @var array
     */
    private $volumes;

    /**
     * @param array $volumes
     */
    public function __construct(array $volumes)
    {
        $this->volumes = $volumes;
    }

    /**
     * @inheritdoc
     */
    public function getCommand(): string
    {
        $commands = [
            'docker-compose down -v',
        ];

        foreach ($this->volumes as $volume) {
            $commands[] = sprintf(
                'docker-compose run %s bash -c "mkdir -p %s"',
                Docker::BUILD_CONTAINER,
                $volume
            );
        }

        $commands[] = 'docker-compose up -d';

        return implode(' && ', $commands);
    }

    /**
     * @inheritdoc
     */
    public function run(): Result
    {
        return $this->executeCommand($this->getCommand());
    }
}
