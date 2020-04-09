<?php

namespace App\DTO\Another;

use App\DTO\DTORequestAbstract;
use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use App\Validator as CustomValidators;

class RegisterDTORequest extends DTORequestAbstract
{
    /**
     * @Assert\NotNull
     * @Assert\Email
     * @CustomValidators\UniqueValueInEntity(
     *     entityClass = User::class,
     *     field = "email"
     * )
     */
    public $email;

    /**
     *  @CustomValidators\Base64Image(
     *     minWidth = 40,
     *     maxWidth = 5000,
     *     minHeight = 40,
     *     maxHeight = 5000
     * )
     */
    public $img_encoded;

    /**
     * @Assert\Length(
     *      min = 2,
     *      max = 50
     * )
     */
    public $name;

    /**
     * @Assert\NotNull
     * @Assert\Regex("/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/")
     */
    public $telephone;


    /**
     * @var string The hashed password
     * @Assert\NotNull
     * @Assert\Length(
     *      min = 6,
     *      max = 50
     * )
     */
    public $password;

    /**
     * @var string The hashed password
     * @Assert\NotNull
     * @Assert\EqualTo(propertyPath = "password")
     */
    public $password_confirmation;

}