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
    private array $possibleRelationshipNames = ['activemq', 'activemq-artemis', 'artemis', 'amq', 'jms'];

    /**
     * Cache for configuration to avoid multiple relationship lookups
     *
     * @var array|null
     */
    private ?array $cachedConfiguration = null;

    /**
     * @var Environment
     */
    private Environment $environment;

    /**
     * @var ShellInterface
     */
    private ShellInterface $shell;

    /**
     * @var string|null
     */
    private ?string $version = null;

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
            $this->version = $this->detectVersion();
        }

        return $this->version;
    }

    /**
     * Detect ActiveMQ version from configuration or system
     *
     * @return string
     */
    private function detectVersion(): string
    {
        $config = $this->getConfiguration();

        if (isset($config['type']) && strpos($config['type'], ':') !== false) {
            return explode(':', $config['type'])[1];
        }

        if (isset($config['host']) && isset($config['port'])) {
            return $this->detectVersionFromSystem();
        }

        return '0';
    }

    /**
     * Detect ActiveMQ version from system using various methods
     *
     * @return string
     */
    private function detectVersionFromSystem(): string
    {
        // Try dpkg for activemq-artemis package
        $version = $this->getVersionFromDpkg('activemq-artemis');
        if ($version !== '0') {
            return $version;
        }

        // Try dpkg for artemis package
        $version = $this->getVersionFromDpkg('artemis');
        if ($version !== '0') {
            return $version;
        }

        // Try CLI commands
        $version = $this->getVersionFromCli('activemq-artemis --version 2>/dev/null | head -1');
        if ($version !== '0') {
            return $version;
        }

        // Try artemis CLI command
        return $this->getVersionFromCli('artemis version 2>/dev/null | head -1');
    }

    /**
     * Get version from dpkg package info
     *
     * @param string $packageName
     * @return string
     */
    private function getVersionFromDpkg(string $packageName): string
    {
        try {
            $process = $this->shell->execute("dpkg -s {$packageName} | grep Version");
            preg_match('/^(?:Version:(?:\s)?)(\d+\.\d+)/', $process->getOutput(), $matches);
            return $matches[1] ?? '0';
        } catch (ShellException $exception) {
            return '0';
        }
    }

    /**
     * Get version from CLI command
     *
     * @param string $command
     * @return string
     */
    private function getVersionFromCli(string $command): string
    {
        try {
            $process = $this->shell->execute($command);
            preg_match('/(?:ActiveMQ|Artemis)\s+(\d+\.\d+)/', $process->getOutput(), $matches);
            return $matches[1] ?? '0';
        } catch (ShellException $exception) {
            return '0';
        }
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
