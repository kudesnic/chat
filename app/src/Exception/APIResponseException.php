<?php

namespace App\Exception;

use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class APIResponseException extends HttpException
{
    /**
     * @var FormInterface
     */
    protected $validationObj;

    /**
     * HttpFormException constructor.
     *
     * @param string|null     $message
     * @param int             $statusCode
     * @param array           $errors
     */
    public function __construct(
        string $message = null,
        int $statusCode = 400,
        array $errors = []
    ) {
        parent::__construct($statusCode, $message, null, [], 0);
    }


}
