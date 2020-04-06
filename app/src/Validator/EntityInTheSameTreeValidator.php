<?php

namespace App\Validator;

use App\Service\JWTUserService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EntityInTheSameTreeValidator extends ConstraintValidator
{

    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var JWTUserService
     */
    private $JWTUserService;
    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(EntityManagerInterface $em, JWTUserService $JWTUserService, RequestStack $requestStack)
    {
        $this->em = $em;
        $this->JWTUserService = $JWTUserService;
        $this->requestStack = $requestStack;
    }

    public function validate($value, Constraint $constraint)
    {
        if($value){
            $entityRepository = $this->em->getRepository($constraint->entityClass);
            if (!is_string($constraint->id)) {
                throw new InvalidArgumentException('"id" parameter should be a string');
            }

            $parentNode = $entityRepository->find($value);
            if(!$parentNode){
                $this->context->buildViolation($constraint->parentDoesntExistMessage)
                    ->setParameter('{{ value }}', $value)
                    ->addViolation();
            } else {
                $currentUser = $this->JWTUserService->getUser($this->requestStack->getCurrentRequest());
                if ($parentNode->getTreeRoot() != $currentUser->getTreeRoot()) {
                    $this->context->buildViolation($constraint->notInTreeMessage)
                        ->setParameter('{{ value }}', $value)
                        ->addViolation();
                }
            }


        }
    }
}
