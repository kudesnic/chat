<?php
namespace App\DTO;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;


class RegisterDTORequest extends DTORequestAbstract
{
    /**
     * @Assert\Email
     * @Assert\NotNull
     */
    public $email;

    /**
     * @Assert\Image(
     *     minWidth = 200,
     *     maxWidth = 6000,
     *     minHeight = 200,
     *     maxHeight = 6000
     * )
     */
    public $img;

    /**
     * @Assert\NotNull
     * @Assert\Length(
     *      min = 2,
     *      max = 50
     * )
     */
    public $name;

    /**
     * @Assert\Regex("/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\./0-9]*$/")
     */
    public $telephone;

    /**
     * @Assert\Choice(choices=App\Entity\User::POSSIBLE_ROLES)
     */
    public $roles = [];

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
     * @Assert\Length(
     *      min = 6,
     *      max = 50
     * )
     */
    public $password_confirmation;



}