<?php

namespace App\Serializer;


use App\Exception\FormException;
use App\Exception\ValidationException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ValidationExceptionNormalizer implements NormalizerInterface
{
    /**
     * @param FormException $exception
     * @param string        $format
     * @param array         $context
     *
     * @return array|bool|float|int|string|void
     */
    public function normalize($exception, string $format = null, array $context = [])
    {
        $data   = [];
        $errors = $exception->getErrors();

        foreach ($errors as $error) {
            $data[$error->getPropertyPath()][] = $error->getMessage();
        }

        return $data;
    }

    /**
     * @param mixed $data
     * @param string  $format
     *
     * @return bool|void
     */
    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof ValidationException;
    }
}