<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class UserController extends Controller
{
    public function login()
    {
        return $this->get('oauth2.registry')
            ->getClient('orbitrondev')
            ->redirect(['user:email', 'user:username', 'user:id']);
    }
}
