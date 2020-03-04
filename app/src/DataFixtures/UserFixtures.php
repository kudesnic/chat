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
        for($i=0; $i<65; $i++){
            $user = new EntityUser();
            $user->setEmail('andrey' . $i . '@gmail.com');
            $user->setPassword($this->encoder->encodePassword($user, '12345678a'));
            $user->setName('Andrey');
            $manager->persist($user);
            $manager->flush();
        }
    }
}
