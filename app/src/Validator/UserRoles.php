<?php

namespace App\Validator;

use App\Entity\User;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UserRoles extends Constraint
{
    /**
     * @return string
     */
    public function getWrongRoleMessage()
    {
        return 'The value {{ value }} is not an allowed role. Allowed roles are:' . implode(', ', User::PUBLIC_ROLES);
    }

    /**
     * @return string
     */
    public function getWrongTypeMessage()
    {
        return 'The value {{ value }} is not an array';
    }


}
