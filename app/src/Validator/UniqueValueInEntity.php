<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueValueInEntity extends Constraint
{
    /*
     * Any public properties become valid options for the annotation.
     * Then, use these in your validator class.
     */
    public $message = 'The value {{ value }} is not unique.';
    public $entityClass;
    public $field;

    public function getRequiredOptions()
    {
        return ['entityClass', 'field'];
    }
}
