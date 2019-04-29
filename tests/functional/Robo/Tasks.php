<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Robo;

use Robo\Collection\CollectionBuilder;
use Robo\TaskAccessor;

/**
 * Tasks loader.
 */
trait Tasks
{
    use TaskAccessor;

    /**
     * @param array $volumes
     * @return Tasks\EnvUp|CollectionBuilder
     */
    protected function taskEnvUp(array $volumes): CollectionBuilder
    {
        return $this->task(Tasks\EnvUp::class, $volumes);
    }

    /**
     * @return Tasks\EnvDown|CollectionBuilder
     */
    protected function taskEnvDown(): CollectionBuilder
    {
        return $this->task(Tasks\EnvDown::class);
    }

    /**
     * @param string $container
     * @return Tasks\Bash|CollectionBuilder
     */
    protected function taskBash(string $container): CollectionBuilder
    {
        return $this->task(Tasks\Bash::class, $container);
    }

    /**
     * @param string $container
     * @return Tasks\DockerCompose\Run|CollectionBuilder
     */
    protected function taskDockerComposeRun(string $container): CollectionBuilder
    {
        return $this->task(Tasks\DockerCompose\Run::class, $container);
    }

    /**
     * @param string $source
     * @param string $destination
     * @param string $container
     * @return Tasks\CopyFromDocker|CollectionBuilder
     */
    protected function taskCopyFromDocker(string $source, string $destination, string $container): CollectionBuilder
    {
        return $this->task(Tasks\CopyFromDocker::class, $source, $destination, $container);
    }

    /**
     * @param string $source
     * @param string $destination
     * @param string $container
     * @return Tasks\CopyToDocker|CollectionBuilder
     */
    protected function taskCopyToDocker(string $source, string $destination, string $container): CollectionBuilder
    {
        return $this->task(Tasks\CopyToDocker::class, $source, $destination, $container);
    }
}
