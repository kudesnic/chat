<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint class checks whether specified entity id belongs to the same tree or not
 *
 * @Annotation
 */
class EntityInTheSameTree extends Constraint
{
    /*
     * Any public properties become valid options for the annotation.
     * Then, use these in your validator class.
     */
    public $notInTreeMessage = 'The parent entity {{ value }} is not in the same tree.';
    public $parentDoesntExistMessage = 'User with {{ id }} = {{ value }} does not exist!';
    public $entityClass;
    public $id;

    public function getRequiredOptions()
    {
        return ['entityClass', 'id'];
    }
}
