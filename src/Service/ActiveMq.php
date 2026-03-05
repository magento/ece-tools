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
 * Returns ActiveMQ Artemis service configurations.
 */
class ActiveMq implements ServiceInterface
{
    /**
     * Possible names for activemq/artemis relationship
     */
    private const POSSIBLE_RELATIONSHIP_NAMES = ['activemq', 'activemq-artemis', 'artemis', 'amq', 'jms'];

    /**
     * Possible package names for version detection
     */
    private const DPKG_PACKAGES = ['activemq-artemis', 'artemis'];

    /**
     * Version regex pattern - supports: 2, 2.42, 2.42.0
     * Captures major and optional minor/patch versions
     */
    private const VERSION_PATTERN = '/^Version:\s?(\d+)(?:\.(\d+))?(?:\.\d+)?/';

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
     * ActiveMq constructor.
     *
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
     * @inheritDoc
     */
    public function getConfiguration(): array
    {
        foreach (self::POSSIBLE_RELATIONSHIP_NAMES as $relationshipName) {
            $mqConfig = $this->environment->getRelationship($relationshipName);
            if (!empty($mqConfig)) {
                return $mqConfig[0];
            }
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function getVersion(): string
    {
        if ($this->version === null) {
            $config = $this->getConfiguration();
            
            // If no configuration exists, return '0' without system detection
            if (empty($config)) {
                $this->version = '0';
                return $this->version;
            }
            
            $this->version = $config['type'] ?? '';

            // Extract version from type string (e.g., "activemq-artemis:2.42")
            if (strpos($this->version, ':') !== false) {
                $this->version = explode(':', $this->version)[1] ?? '0';
            } elseif (empty($this->version) || $this->version === 'activemq-artemis') {
                // Fall back to system detection if config exists but no version in type field
                $this->version = $this->detectVersionFromSystem();
            }
        }

        return $this->version;
    }

    /**
     * Detect version from system using dpkg
     *
     * @return string
     */
    private function detectVersionFromSystem(): string
    {
        // Try different package names
        foreach (self::DPKG_PACKAGES as $packageName) {
            $version = $this->getVersionFromDpkg($packageName);
            if ($version !== '0') {
                return $version;
            }
        }

        return '0';
    }

    /**
     * Get version from dpkg package info
     * Returns normalized version in major.minor format (e.g., "2.42")
     * If only major version exists, returns it (e.g., "2")
     *
     * @param string $packageName
     * @return string
     */
    private function getVersionFromDpkg(string $packageName): string
    {
        try {
            $process = $this->shell->execute("dpkg -s {$packageName} | grep Version");
            preg_match(self::VERSION_PATTERN, $process->getOutput(), $matches);
            
            if (!isset($matches[1])) {
                return '0';
            }
            
            // Normalize version: return major.minor (strip patch version)
            // Examples: "2.42.1" → "2.42", "2.42" → "2.42", "2" → "2"
            $major = $matches[1];
            $minor = $matches[2] ?? null;
            
            return $minor !== null ? "{$major}.{$minor}" : $major;
        } catch (ShellException $exception) {
            return '0';
        }
    }

    /**
     * Check if ActiveMQ is available (any configuration present)
     *
     * @return bool
     */
    public function isStompEnabled(): bool
    {
        return !empty($this->getConfiguration());
    }
}
