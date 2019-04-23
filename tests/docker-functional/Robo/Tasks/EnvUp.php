<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\DockerFunctional\Robo\Tasks;

use Robo\Common\ExecOneCommand;
use Robo\Contract\CommandInterface;
use Robo\Contract\TaskInterface;
use Robo\Result;
use Robo\Task\BaseTask;

/**
 * Up Docker environment
 */
class EnvUp extends BaseTask implements CommandInterface, TaskInterface
{
    use ExecOneCommand;

    /**
     * @inheritdoc
     */
    public function getCommand(): string
    {
        return 'docker-compose down -v && docker-compose up -d';
    }

    /**
     * @inheritdoc
     */
    public function run(): Result
    {
        return $this->executeCommand($this->getCommand());
    }
}
