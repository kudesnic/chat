<?php

namespace App\DataFixtures;

use App\Entity\User as EntityUser;
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
        $prev = null;
        for($i=0; $i<30; $i++){
            $user = new EntityUser();
            $user->setEmail('andrey' . $i . $treeKey . '@gmail.com');
            $user->setPassword($this->encoder->encodePassword($user, '12345678a'));
            $user->setName('Andrey');
            if(is_null($prev) == false){
                $user->setParent($prev);
            }
            $manager->persist($user);
            $manager->flush();
            $prev = $user;
        }
    }
}
