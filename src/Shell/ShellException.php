<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Shell;

/**
 * Wrapper for exceptions coming from shell commands.
 */
class ShellException extends \RuntimeException
{
    /**
     * @var string[]
     */
    private $output;

    /**
     * @param string $message
     * @param int $code
     * @param string[] $ouptut
     * @param Throwable $previous
     */
    public function __construct(string $message, int $code, array $ouptut = [], \Throwable $previous = null)
    {
        $this->output = $output;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string[]
     */
    public function getOutput(): array
    {
        return $output;
    }
}
