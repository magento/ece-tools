<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator;

class Result
{
    /**
     * @var string[]
     */
    private $errors;

    /**
     * @var string
     */
    private $suggestion = '';

    /**
     * Adds error to the list of errors
     *
     * @param string $error
     */
    public function addError(string $error)
    {
        $this->errors[] = $error;
    }

    /**
     * Checks if at least one error was added
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return (bool)count($this->errors);
    }

    /**
     * Returns list of errors
     *
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return string
     */
    public function getSuggestion(): string
    {
        return $this->suggestion;
    }

    /**
     * @param string $suggestion
     */
    public function setSuggestion(string $suggestion)
    {
        $this->suggestion = $suggestion;
    }
}
