<?php

namespace App\Exception;

use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Validator\ConstraintViolationListInterface;

interface ValidationExceptionInterface
{
    /**
     * @return ConstraintViolationListInterface|FormErrorIterator
     */
    public function getErrors();
}