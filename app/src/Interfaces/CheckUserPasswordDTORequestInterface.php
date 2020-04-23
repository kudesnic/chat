<?php

namespace App\Interfaces;

use App\Service\JWTUserService;
use Symfony\Component\HttpFoundation\Request;

interface CheckUserPasswordDTORequestInterface
{

    /**
     * CheckUserPasswordDTORequestInterface constructor.
     * @param Request $request
     * @param JWTUserService $JWTUserService
     */
    public function __construct(Request $request, JWTUserService $JWTUserService);

    /**
     * Checks whether user specified correct password or not
     * @param string $value
     * @return bool
     */
    public function checkUserPassword(string $value):bool;

}