<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class Login
{

    /**
     * @Assert\NotBlank
     * @Assert\Email
     */
    public $email;

    /**
     * @Assert\NotBlank
     * @Assert\Length(min=6)
     * 
     */
    public $password;

}
