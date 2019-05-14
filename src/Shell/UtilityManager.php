<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Shell;

/**
 * Helps to determine correct path to system utilities.
 */
class UtilityManager
{
    const UTILITY_TIMEOUT = 'timeout';
    const UTILITY_BASH = 'bash';

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
     * @throws \RuntimeException If utility does not present in the system
     */
    public function get(string $utility): string
    {
        $utilities = $this->getUtilities();

        if (array_key_exists($utility, $utilities)) {
            return $utilities[$utility];
        }

        throw new \RuntimeException(sprintf(
            'Utility %s not found',
            $utility
        ));
    }

    /**
     * @return array
     */
    private function getUtilities(): array
    {
        if (null === $this->utilities) {
            $list = [
                self::UTILITY_TIMEOUT,
                self::UTILITY_BASH,
            ];

            foreach ($list as $name) {
                try {
                    $process = $this->shell->execute('which ' . $name);
                    $this->utilities[$name] = explode(PHP_EOL, $process->getOutput())[0];
                } catch (\Exception $exception) {
                    throw new \RuntimeException(sprintf(
                        'Required utility %s was not found',
                        $name
                    ));
                }
            }
        }

        return $this->utilities;
    }
}
