<?php
namespace App\DTO\Update;

use App\DTO\DTORequestAbstract;
use App\Entity\User;
use App\Interfaces\CheckUserPasswordDTORequestInterface;
use App\Repository\UserRepository;
use App\Service\JWTUserService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use App\Validator as CustomValidators;

class UserUpdateDTORequest extends DTORequestAbstract implements CheckUserPasswordDTORequestInterface
{
    private $JWTUserService;

    public function __construct(
        Request $request,
        JWTUserService $JWTUserService
    ) {
        $this->JWTUserService = $JWTUserService;
        parent::__construct($request);
    }

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
     * @Assert\Regex("/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/")
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

    /**
     * @var string
     * @Assert\Expression(
     *     "(!this.password  ) || (this.password  && this.checkUserPassword(value) == true)",
     *     message="Wrong user password!"
     * );
     */
    public $old_password;

    /**
     * @var string The hashed password
     *
     * @Assert\Expression(
     *     "(!value) || (value != null && this.old_password != null)",
     *     message="Old password required!"
     * );
     * @Assert\Length(
     *      min = 6,
     *      max = 50
     * )
     */
    public $password;

    /**
     * @var string The hashed password
     * @Assert\EqualTo(propertyPath = "password")
     */
    public $password_confirmation;

    /**
     * Checks whether user specified correct password or not
     * @param string $value
     * @return bool
     */
    public function checkUserPassword(?string $value):bool
    {
        //$value can be null if we dont update password. So we si,ply don't specify password properties
        if ($value) {
            $result = $this->JWTUserService->checkPassword($this->request, $value);
        } else {
            $result = true;
        }

        return $result;
    }

}