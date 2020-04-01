<?php

namespace App\Validator;

use App\Service\Base64ImageService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\ImageValidator;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class Base64ImageValidator extends ImageValidator
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var Base64ImageService
     */
    private $base64ImageService;

    public function __construct(EntityManagerInterface $em, ValidatorInterface $validator, Base64ImageService $base64ImageService)
    {
        $this->em = $em;
        $this->validator = $validator;
        $this->base64ImageService = $base64ImageService;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Base64Image) {
            throw new UnexpectedTypeException($constraint, Base64Image::class);
        }

        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) take care of that
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            // throw this exception if your validator cannot handle the passed type so that it can be marked as invalid
            throw new UnexpectedValueException($value, 'string');

            // separate multiple types using pipes
            // throw new UnexpectedValueException($value, 'string|int');
        }

        $file = $this->base64ImageService->convertToFile($value);

        return parent::validate($file, $constraint);
    }

}
