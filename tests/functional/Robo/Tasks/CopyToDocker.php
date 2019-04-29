<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Robo\Tasks;

use Robo\Common\ExecOneCommand;
use Robo\Contract\CommandInterface;
use Robo\Contract\TaskInterface;
use Robo\Result;
use Robo\Task\BaseTask;

/**
 * Copy files to Docker environment
 */
class CopyToDocker extends BaseTask implements CommandInterface, TaskInterface
{
    use ExecOneCommand;

    /**
     * Container name
     *
     * @var string
     */
    protected $container;

    /**
     * Path to file on the Docker environment
     *
     * @var string
     */
    protected $source;

    /**
     * Path to file on the local machine
     *
     * @var string
     */
    protected $destination;

    /**
     * @param string $source
     * @param string $destination
     * @param string $container
     */
    public function __construct(string $source, string $destination, string $container)
    {
        $this->container = $container;
        $this->source = $source;
        $this->destination = $destination;
    }

    /**
     * @inheritdoc
     */
    public function getCommand(): string
    {
        return sprintf(
            'docker cp %s %s:%s',
            $this->source,
            $this->container,
            $this->destination
        );
    }

    /**
     * @inheritdoc
     */
    public function run(): Result
    {
        if (!file_exists($this->source)) {
            throw new \RuntimeException(sprintf('The path "%s" does not exist', $this->source));
        }

        return $this->executeCommand($this->getCommand());
    }
}
