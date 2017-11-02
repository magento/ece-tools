<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Result;

use Magento\MagentoCloud\Config\Validator\ResultInterface;

class Error implements ResultInterface
{
    /**
     * @var string
     */
    private $error;

    /**
     * @var string
     */
    private $suggestion = '';

    /**
     * @param string $error
     * @param string $suggestion
     */
    public function __construct(string $error, string $suggestion = '')
    {
        $this->error = $error;
        $this->suggestion = $suggestion;
    }

    /**
     * Returns error
     *
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * @return string
     */
    public function getSuggestion(): string
    {
        return $this->suggestion;
    }
}
