<?php
namespace Twogether\LaravelURLSigner\Exceptions;

class InvalidSignedUrl
    extends \Exception
{
    private $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct($errors[0] ?? '');
    }

    public function errors(): array
    {
        return $this->errors;
    }
}