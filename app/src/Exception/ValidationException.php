<?php

namespace App\Exception;

use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationException extends HttpException
{
    /**
     * @var FormInterface
     */
    protected $validationObj;

    /**
     * HttpFormException constructor.
     *
     * @param ConstraintViolationListInterface   $validationObj
     * @param int             $statusCode
     * @param string|null     $message
     * @param \Exception|null $previous
     * @param array           $headers
     * @param int|null        $code
     */
    public function __construct(
        ConstraintViolationListInterface $validationObj,
        int $statusCode = 400,
        string $message = null,
        \Exception $previous = null,
        array $headers = [],
        ?int $code = 0
    ) {
        parent::__construct($statusCode, $message, $previous, $headers, $code);

        $this->validationObj = $validationObj;
    }

    /**
     * @return ConstraintViolationListInterface
     */
    public function getObject():ConstraintViolationListInterface
    {
        return $this->validationObj;
    }

    /**
     * @return FormErrorIterator
     */
    public function getErrors()
    {
        return $this->validationObj;
    }
}
