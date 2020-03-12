<?php
namespace App\DTO\Another;

use App\DTO\DTORequestAbstract;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
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
     * @Assert\EqualTo(propertyPath = "password")
     */
    public $password_confirmation;

}