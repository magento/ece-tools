<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator;

class Result
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
    public function __construct(string $error = '', string $suggestion = '')
    {
        $this->error = $error;
        $this->suggestion = $suggestion;
    }

    /**
     * Checks if at least one error was added
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return !empty($this->error);
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
