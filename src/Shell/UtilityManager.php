<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Shell;

/**
 * Helps to determine correct path to system utilities.
 */
class UtilityManager
{
    public const UTILITY_TIMEOUT = 'timeout';
    public const UTILITY_SHELL = 'bash';
    /**
     * @deprecated
     */
    public const UTILITY_BASH = self::UTILITY_SHELL;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var array
     */
    private $utilities;

    /**
     * @param ShellInterface $shell
     */
    public function __construct(ShellInterface $shell)
    {
        $this->shell = $shell;
    }

    /**
     * Retrieves system path to given utility.
     *
     * @param string $utility
     * @return string
     * @throws UtilityException If utility does not present in the system
     */
    public function get(string $utility): string
    {
        $utilities = $this->getUtilities();

        if (array_key_exists($utility, $utilities)) {
            return $utilities[$utility];
        }

        throw new UtilityException(sprintf(
            'Utility %s not found',
            $utility
        ));
    }

    /**
     * @return array
     *
     * @throws UtilityException
     */
    private function getUtilities(): array
    {
        if (null === $this->utilities) {
            $list = [
                self::UTILITY_TIMEOUT,
                self::UTILITY_SHELL,
            ];

            foreach ($list as $name) {
                try {
                    $process = $this->shell->execute('which ' . $name);
                    $this->utilities[$name] = explode(PHP_EOL, $process->getOutput())[0];
                } catch (ShellException $exception) {
                    throw new UtilityException(
                        sprintf('Required utility %s was not found', $name),
                        $exception->getCode(),
                        $exception
                    );
                }
            }
        }

        return $this->utilities;
    }
}
