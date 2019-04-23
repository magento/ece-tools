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
 * Down Docker environment
 */
class EnvDown extends BaseTask implements CommandInterface, TaskInterface
{
    use ExecOneCommand;

    /**
     * @inheritdoc
     */
    public function getCommand(): string
    {
        return 'docker-compose down -v';
    }

    /**
     * @inheritdoc
     */
    public function run(): Result
    {
        return $this->executeCommand($this->getCommand());
    }
}
