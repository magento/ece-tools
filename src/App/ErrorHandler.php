<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App;

/**
 * An error handler that converts runtime errors into exceptions.
 */
class ErrorHandler
{
    /**
     * Error messages
     *
     * @var array
     */
    private static $errorPhrases = [
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict Notice',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated Functionality',
        E_USER_DEPRECATED => 'User Deprecated Functionality',
    ];

    /**
     * Custom error handler.
     *
     * @param int $errorNo
     * @param string $errorStr
     * @param string $errorFile
     * @param int $errorLine
     * @return bool
     * @throws \RuntimeException
     */
    public function handle(int $errorNo, string $errorStr, string $errorFile, int $errorLine): bool
    {
        if (strpos($errorStr, 'DateTimeZone::__construct') !== false) {
            /**
             * There's no way to distinguish between caught system exceptions and warnings.
             */
            return false;
        }

        $errorNo &= error_reporting();

        if ($errorNo === 0) {
            return false;
        }

        $msg = self::$errorPhrases[$errorNo] ?? "Unknown error ({$errorNo})";
        $msg .= ": {$errorStr} in {$errorFile} on line {$errorLine}";

        throw new \RuntimeException($msg);
    }
}
