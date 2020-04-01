<?php

namespace App\Validator;

use App\Entity\User;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\ImageValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UserRolesValidator extends ImageValidator
{

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof UserRoles) {
            throw new UnexpectedTypeException($constraint, UserRoles::class);
        }

        if (!is_array($value)) {
            $this->context->buildViolation($constraint->getWrongRoleMessage())
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        } else {
            foreach ($value as $role){
                if(in_array($role, User::PUBLIC_ROLES) == false){
                    $this->context->buildViolation($constraint->getWrongRoleMessage())
                        ->setParameter('{{ value }}', $role)
                        ->addViolation();
                }
            }
        }

    }

}
