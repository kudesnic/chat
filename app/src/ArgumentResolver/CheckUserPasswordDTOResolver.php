<?php

namespace App\ArgumentResolver;

use App\Interfaces\RequestDTOInterface;
use App\Exception\ValidationException;
use App\Interfaces\CheckUserPasswordDTORequestInterface;
use App\Service\JWTUserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CheckUserPasswordDTOResolver implements ArgumentValueResolverInterface
{
    private $validator;
    private $JWTUserService;

    /**
     * RequestDTOResolver constructor.
     * @param ValidatorInterface $validator
     * @param JWTUserService $JWTUserService
     */
    public function __construct(ValidatorInterface $validator, JWTUserService $JWTUserService)
    {
        $this->validator = $validator;
        $this->JWTUserService = $JWTUserService;
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     * @return bool
     * @throws \ReflectionException
     */
    public function supports(Request $request, ArgumentMetadata $argument)
    {

        $reflection = new \ReflectionClass($argument->getType());

        if ($reflection->implementsInterface(CheckUserPasswordDTORequestInterface::class)) {
            return true;
        }

        return false;
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     * @return \Generator|iterable
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {

        // creating new instance of custom request DTO
        $class = $argument->getType();
        $dto = new $class($request, $this->JWTUserService);

        // throw validation exception in case of invalid request data
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }

        yield $dto;
    }
}