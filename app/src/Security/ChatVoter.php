<?php
/**
 * Created by PhpStorm.
 * User: andrey
 * Date: 09.09.20
 * Time: 19:37
 */

namespace App\Security;


use App\Entity\Chat;
use App\Entity\User;
use App\Repository\ChatRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;

class ChatVoter extends Voter
{

    const VIEW = 'view';
    const WRITE = 'write';
    const EDIT = 'edit';
    const DELETE = 'delete';

    /**
     * @var Security
     */
    private $security;

    /**
     * @var ChatRepository
     */
    private $chatRepository;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * UserRepository $userRepository
     */
    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security = $security;
        $this->chatRepository = $entityManager->getRepository(Chat::class);
        $this->userRepository = $entityManager->getRepository(User::class);
    }

    /**
     * Determines if the attribute and subject are supported by this voter.
     *
     * @param string $attribute An attribute
     * @param mixed  $subject   The subject to secure, e.g. an object the user wants to access or any other PHP type
     *
     * @return bool True if the attribute and subject are supported, false otherwise
     */
    protected function supports(string $attribute, $subject)
    {

        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::WRITE])) {
            return false;
        }

        // only vote on `Chat` objects
        if (!$subject instanceof Chat) {
            return false;
        }

        return true;
    }

    /**
     * Perform a single access check operation on a given attribute, subject and token.
     * It is safe to assume that $attribute and $subject already passed the "supports()" method check.
     *
     * @param mixed $subject
     *
     * @return bool
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token)
    {
        if ($this->security->isGranted(User::ROLE_SUPER_ADMIN)) {
            return true;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        $chat = $subject;

        switch ($attribute) {
            case self::WRITE:
            case self::VIEW:
                return $this->canView($chat, $user);

            case self::EDIT:
            case self::DELETE:
                return $this->canDeleteOrEdit($chat, $user);

            default:
                throw new \LogicException('This code should not be reached!');
        }

    }


    private function canView(Chat $chat, User $user): bool
    {
        return $this->chatRepository->hasAccessToChat($chat, $user);
    }

    private function canDeleteOrEdit(Chat $chat, User $user): bool
    {
        return $user->getId() == $chat->getOwnerId() ||
            $this->userRepository->isNodeAChild($user, $chat->getOwnerId()) ;
    }
}