<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator;

use Magento\MagentoCloud\App\GenericException;

/**
 * Exception for validation purposes.
 */
class ValidatorException extends GenericException
{
    /**
     * @var string
     */
    private $suggestion;

    /**
     * @inheritDoc
     */
    public function __construct(string $message, string $suggestion = '', int $code = 0, \Throwable $previous = null)
    {
        $this->suggestion = $suggestion;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns suggestion message
     *
     * @return string
     */
    public function getSuggestion(): string
    {
        return $this->suggestion;
    }
}
