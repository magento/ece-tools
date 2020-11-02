<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;

/**
 *
 */
class RabbitMq implements ServiceInterface
{
    /**
     * Possible names for amqp relationship
     *
     * @var array
     */
    private $possibleRelationshipNames = ['rabbitmq', 'mq', 'amqp'];

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var string
     */
    private $version;

    /**
     * @param Environment $environment
     * @param ShellInterface $shell
     */
    public function __construct(
        Environment $environment,
        ShellInterface $shell
    ) {
        $this->environment = $environment;
        $this->shell = $shell;
    }

    /**
     * Finds if configuration exists for one of possible amqp relationship names and return first match,
     * amqp relationship can have different name on different environment.
     *
     * {@inheritDoc}
     */
    public function getConfiguration(): array
    {
        foreach ($this->possibleRelationshipNames as $relationshipName) {
            $mqConfig = $this->environment->getRelationship($relationshipName);
            if (count($mqConfig)) {
                return $mqConfig[0];
            }
        }

        return [];
    }

    /**
     * Retrieve RabbitMQ service version whether from relationship configuration
     * or using CLI command (for PRO environments)
     *
     * {@inheritDoc}
     */
    public function getVersion(): string
    {
        if ($this->version === null) {
            $this->version = '0';

            $config = $this->getConfiguration();

            if (isset($config['type']) && strpos($config['type'], ':') !== false) {
                $this->version = explode(':', $config['type'])[1];
            } elseif (isset($config['host']) && isset($config['port'])) {
                try {
                    $process = $this->shell->execute('dpkg -s rabbitmq-server | grep Version');
                    preg_match('/^(?:Version:(?:\s)?)(\d+\.\d+)/', $process->getOutput(), $matches);
                    $this->version = $matches[1] ?? '0';
                } catch (ShellException $exception) {
                    throw new ServiceException($exception->getMessage());
                }
            }
        }

        return $this->version;
    }
}
