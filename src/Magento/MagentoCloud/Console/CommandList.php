<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Console;

use Magento\Framework\ObjectManagerInterface;

/**
 * Class CommandList
 */
class CommandList implements \Magento\Framework\Console\CommandListInterface
{
    /**
     * Gets list of command classes
     *
     * @return string[]
     */
    protected function getCommandsClasses()
    {
        return [
            'Magento\MagentoCloud\Console\Command\Build',
            'Magento\MagentoCloud\Console\Command\Deploy'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getCommands()
    {
        $commands = [];
        foreach ($this->getCommandsClasses() as $class) {
            if (class_exists($class)) {
                $commands[] = new $class();
            } else {
                throw new \Exception('Class ' . $class . ' does not exist');
            }
        }
        return $commands;
    }
}