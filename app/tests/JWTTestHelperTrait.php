<?php

namespace App\Tests;

trait JWTTestHelperTrait 
{
    /**
     * Create a client with a default Authorization header.
     *
     * @param string $email
     * @param string $password
     *
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    protected function createAuthenticatedClient($email = 'user', $password = 'password')
    {
        if (static::$booted == false) {
            $client = static::createClient();
        } else {
            $client = static::$kernel->getContainer()->get('test.client');
        }
//        $client = static::createClient();
        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $email,
                'password' => $password,
            ])
        );

        $data = json_decode($client->getResponse()->getContent(), true);

        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $data['token']));
        $client->setServerParameter('CONTENT_TYPE', 'application/json');

        return $client;
    }

}