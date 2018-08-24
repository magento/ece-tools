<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
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
     * @param string $message
     * @param string $suggestion
     */
    public function __construct(string $message, string $suggestion = '')
    {
        $this->message = $message;
        $this->suggestion = $suggestion;
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
     * @return string
     */
    public function __toString(): string
    {
        return $this->getError();
    }
}
