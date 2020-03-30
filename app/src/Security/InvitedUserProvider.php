<?php

namespace App\Security;


use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bridge\Doctrine\Security\User\EntityUserProvider;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * User provider for extracting of invited users.
 *
 */
class InvitedUserProvider extends EntityUserProvider implements UserProviderInterface
{
    private $registry;
    private $managerName;
    private $classOrAlias;
    private $property;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
        $this->classOrAlias = User::class;
        $this->property = 'email';

        parent::__construct($registry, $this->classOrAlias, $this->property, null);
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername(string $username)
    {
        $repository = $this->getRepository();
        if (null !== $this->property) {
            $user = $repository->findOneBy([
                $this->property => $username,
                'status' => User::STATUS_INVITED
            ]);
        } else {
            if (!$repository instanceof UserLoaderInterface) {
                throw new \InvalidArgumentException(sprintf('You must either make the "%s" entity Doctrine Repository ("%s") implement "Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface" or set the "property" option in the corresponding entity provider configuration.', $this->classOrAlias, \get_class($repository)));
            }

            $user = $repository->loadUserByUsername($username);
        }

        if (null === $user) {
            throw new UsernameNotFoundException(sprintf('User "%s" not found.', $username));
        }

        return $user;
    }


    /**
     * Refreshes the user after being reloaded from the session.
     *
     * When a user is logged in, at the beginning of each request, the
     * User object is loaded from the session and then this method is
     * called. Your job is to make sure the user's data is still fresh by,
     * for example, re-querying for fresh User data.
     *
     * If your firewall is "stateless: true" (for a pure API), this
     * method is not called.
     *
     * @return UserInterface
     */
    public function refreshUser(UserInterface $user)
    {
        parent::refreshUser($user);
    }

    /**
     * Tells Symfony to use this provider for this User class.
     */
    public function supportsClass($class)
    {
        return User::class === $class;
    }

    private function getRepository(): ObjectRepository
    {
        return $this->getObjectManager()->getRepository($this->classOrAlias);
    }

    private function getObjectManager(): ObjectManager
    {
        return $this->registry->getManager($this->managerName);
    }

}
