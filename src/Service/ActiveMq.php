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
 * Service class for ActiveMQ Artemis
 */
class ActiveMq implements ServiceInterface
{
    /**
     * Possible names for activemq/artemis relationship
     *
     * @var array
     */
    private $possibleRelationshipNames = ['activemq', 'activemq-artemis', 'artemis', 'amq', 'jms'];

    /**
     * Cache for configuration to avoid multiple relationship lookups
     *
     * @var array|null
     */
    private $cachedConfiguration;

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
     * @param Environment    $environment
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
     * Finds if configuration exists for one of possible activemq relationship names and return first match,
     * activemq relationship can have different name on different environment.
     *
     * {@inheritDoc}
     */
    public function getConfiguration(): array
    {
        if ($this->cachedConfiguration === null) {
            $this->cachedConfiguration = [];
            foreach ($this->possibleRelationshipNames as $relationshipName) {
                $mqConfig = $this->environment->getRelationship($relationshipName);
                if (count($mqConfig)) {
                    $this->cachedConfiguration = $mqConfig[0];
                    break;
                }
            }
        }

        return $this->cachedConfiguration;
    }

    /**
     * Retrieve ActiveMQ service version whether from relationship configuration
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
                    // Try to get ActiveMQ version from dpkg first (for ActiveMQ)
                    $process = $this->shell->execute('dpkg -s activemq-artemis | grep Version');
                    preg_match('/^(?:Version:(?:\s)?)(\d+\.\d+)/', $process->getOutput(), $matches);
                    $this->version = $matches[1] ?? '0';
                } catch (ShellException $exception) {
                    try {
                        // Try artemis package if activemq package not found
                        $process = $this->shell->execute('dpkg -s artemis | grep Version');
                        preg_match('/^(?:Version:(?:\s)?)(\d+\.\d+)/', $process->getOutput(), $matches);
                        $this->version = $matches[1] ?? '0';
                    } catch (ShellException $artemisException) {
                        try {
                            // Fallback: Try ActiveMQ CLI command
                            $process = $this->shell->execute('activemq-artemis --version 2>/dev/null | head -1');
                            preg_match('/(?:ActiveMQ|Artemis)\s+(\d+\.\d+)/', $process->getOutput(), $matches);
                            $this->version = $matches[1] ?? '0';
                        } catch (ShellException $cliException) {
                            try {
                                // Try artemis CLI command
                                $process = $this->shell->execute('artemis version 2>/dev/null | head -1');
                                preg_match('/(?:ActiveMQ|Artemis)\s+(\d+\.\d+)/', $process->getOutput(), $matches);
                                $this->version = $matches[1] ?? '0';
                            } catch (ShellException $fallbackException) {
                                // If all methods fail, default to '0' (don't throw exception)
                                $this->version = '0';
                            }
                        }
                    }
                }
            }
        }

        return $this->version;
    }

    /**
     * Check if ActiveMQ is available (any configuration present)
     * This determines if STOMP should be used with hardcoded values
     *
     * @return bool
     */
    public function isStompEnabled(): bool
    {
        $config = $this->getConfiguration();
        return !empty($config);
    }
}
