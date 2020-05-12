<?php

namespace App\DataFixtures;

use App\Entity\User as EntityUser;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
{

    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {
        for($i=0; $i<2; $i++) {
            $this->generateOneTree($manager, $i);
        }
    }

    /**
     * Generate 30 users with hierarchy
     *
     * @param ObjectManager $manager
     * @param $treeKey
     */
    public function generateOneTree(ObjectManager $manager, $treeKey):void
    {
        $superAdmin = new EntityUser();
        $superAdmin->setEmail('andrey-super-admin-' .  $treeKey . '@gmail.com');
        $superAdmin->setPassword($this->encoder->encodePassword($superAdmin, '12345678a'));
        $superAdmin->setName('Andrey');
        $superAdmin->setStatus(User::STATUS_ACTIVE);
        $superAdmin->setRoles([User::ROLE_SUPER_ADMIN]);
        $manager->persist($superAdmin);
        $manager->flush();

        $admin = new EntityUser();
        $admin->setEmail('andrey-admin' .  $treeKey . 0 . '@gmail.com');
        $admin->setPassword($this->encoder->encodePassword($admin, '12345678a'));
        $admin->setName('Andrey');
        $admin->setStatus(User::STATUS_ACTIVE);
        $admin->setRoles([User::ROLE_ADMIN]);
        $admin->setParent($superAdmin);
        $manager->persist($admin);
        $manager->flush();

        $admin = new EntityUser();
        $admin->setEmail('andrey-admin' .  $treeKey . 1 . '@gmail.com');
        $admin->setPassword($this->encoder->encodePassword($admin, '12345678a'));
        $admin->setName('Andrey');
        $admin->setStatus(User::STATUS_ACTIVE);
        $admin->setRoles([User::ROLE_ADMIN]);
        $admin->setParent($superAdmin);
        $manager->persist($admin);
        $manager->flush();

        for($i=0; $i<3; $i++){
            $user = new EntityUser();
            $user->setEmail('andrey' . $i . $treeKey . '@gmail.com');
            $user->setPassword($this->encoder->encodePassword($user, '12345678a'));
            $user->setName('Andrey');
            $user->setStatus(User::STATUS_ACTIVE);
            $user->setRoles([User::ROLE_MANAGER]);
            $user->setParent($admin);
            $manager->persist($user);
            $manager->flush();
        }
    }
}
