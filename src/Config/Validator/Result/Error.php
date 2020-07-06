<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Result;

use Magento\MagentoCloud\Config\Validator\ResultInterface;

/**
 * @inheritdoc
 */
class Error implements ResultInterface
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var string
     */
    private $suggestion;

    /**
     * @var integer|null
     */
    private $code;

    /**
     * @param string $message
     * @param string $suggestion
     * @param int|null $code
     */
    public function __construct(string $message, string $suggestion = '', int $code = null)
    {
        $this->message = $message;
        $this->suggestion = $suggestion;
        $this->code = $code;
    }

    /**
     * Returns error
     *
     * @return string
     */
    public function getError(): string
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getSuggestion(): string
    {
        return $this->suggestion;
    }

    /**
     * @return integer|null
     */
    public function getErrorCode(): ?int
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getError();
    }
}
