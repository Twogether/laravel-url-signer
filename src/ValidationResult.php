<?php
namespace Twogether\LaravelURLSigner;

class ValidationResult
{
    private $is_valid;
    private $_errors;

    public function __construct(array $errors)
    {
        $this->is_valid = count($errors) === 0;
        $this->_errors = $errors;
    }

    public function isValid(): bool
    {
        return $this->is_valid;
    }

    public function errors(): array
    {
        return $this->_errors;
    }
}