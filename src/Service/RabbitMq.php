<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Service;

use Magento\MagentoCloud\Config\Environment;

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
     * @var string
     */
    private $version;

    /**
     * @param Environment $environment
     */
    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * Finds if configuration exists for one of possible amqp relationship names and return first match,
     * amqp relationship can have different name on different environment.
     *
     * {@inheritdoc}
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
     * @inheritdoc
     */
    public function getVersion(): string
    {
        if ($this->version === null) {
            $this->version = '0';

            $config = $this->getConfiguration();

            if (isset($config['type']) && strpos($config['type'], ':') !== false) {
                $this->version = explode(':', $config['type'])[1];
            }
        }

        return $this->version;
    }
}
