<?php

namespace App\Controller;

use Exception;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\User\OAuthUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class UserController extends AbstractController
{
    /**
     * @Route("/login", name="login")
     *
     * @param ClientRegistry $clientRegistry The OAuth2 client registry
     *
     * @return RedirectResponse
     */
    public function login(ClientRegistry $clientRegistry)
    {
        /** @var OAuthUser $user */
        $user = $this->getUser();
        if ($user instanceof UserInterface) {
            return $this->redirectToRoute('index');
        }

        return $clientRegistry
            ->getClient('generation2')
            ->redirect(['user:email', 'user:username', 'user:id'])
            ;
    }

    /**
     * This is the route the user can use to logout.
     *
     * But, this will never be executed. Symfony will intercept this first
     * and handle the logout automatically. See logout in config/packages/security.yaml
     *
     * @Route("/logout", name="logout")
     *
     * @throws Exception
     */
    public function logout(): void
    {
        throw new Exception('This should never be reached!');
    }

    /**
     * This is the route the user can use to logout.
     *
     * But, this will never be executed. Symfony will intercept this first
     * and handle the logout automatically. See logout in config/packages/security.yaml
     *
     * @Route("/login-check", name="login_check")
     *
     * @throws Exception
     */
    public function loginCheck(): void
    {
        throw new Exception('This should never be reached!');
    }
}
