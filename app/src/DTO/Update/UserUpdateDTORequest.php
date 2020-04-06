<?php
namespace App\DTO\Update;

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

class UserUpdateDTORequest extends DTORequestAbstract
{
    /**
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
     * @Assert\Regex("/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\./0-9]*$/")
     */
    public $telephone;

    /**
     * @CustomValidators\UserRoles
     */
    public $roles = [];

    /**
     * @CustomValidators\EntityInTheSameTree(entityClass = User::class, id = "id")
     */
    public $parent_id = [];

}