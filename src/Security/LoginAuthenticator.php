<?php

namespace App\Security;

use App\Entity\Utilisateur;
use App\Service\CryptageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use JMS\Serializer\SerializerInterface;

class LoginAuthenticator extends AbstractGuardAuthenticator
{
    private $em;
    private const LOGIN_ROUTE = 'app_login';
    private $passwordEncoder;
    private $container;
    private $cryptageService;
    private $serializer;

    public function __construct(
        EntityManagerInterface $em,
        ContainerInterface $container,
        CryptageService $cryptageService,
        SerializerInterface $serializer,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        $this->em = $em;
        $this->container = $container;
        $this->cryptageService = $cryptageService;
        $this->serializer = $serializer;
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * Méthode appelée à chaque requête pour savoir si le loginAuthenticator doit s'activer.
     * Si on retourne "false", il n'est pas activé et on laisse passer la requête.
     */
    public function supports(Request $request)
    {
        return self::LOGIN_ROUTE === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }

    /**
     * Méthode appelée à chaque requête pour récupérer le login et le mot de passe envoyé par l'utilisateur.
     * Ils sont alors transmis à getUser().
     */
    public function getCredentials(Request $request)
    {
        $credentials = [
            'login' => $request->request->get('login'),
            'password' => $request->request->get('pwd')
        ];

        return $credentials;
    }

    /**
     * Récupère l'utilisateur depuis la base de données à partir de son login
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $user = $this->em->getRepository(Utilisateur::class)->findOneBy(['login' => $credentials['login']]);
        if (!$user) {
            throw new AuthenticationException('Login et/ou mot de passe invalide(s)');
        }

        // Vérification du profil de l'utilisateur
        $unauthorizedProfiles = $this->container->getParameter('unauthorizedProfiles');
        if ($unauthorizedProfiles && in_array($user->getProfil()->getId(), $unauthorizedProfiles)) {
            throw new AuthenticationException('Utilisateur non autorisé');
        }

        return $user;
    }

    /**
     * Vérifie le mot de passe de l'utilisateur ("hashé" dans la base)
     */
    public function checkCredentials($credentials, UserInterface $user)
    {// Si Arca est désactivé
            if ($this->container->getParameter('passwordHashing')) {
                return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
            } else {
                return 0 === strcmp($credentials['password'], $user->getPassword());
            }
    }

    /**
     * Méthode appelée si l'authentification est un succés.
     * On envoie alors l'utilisateur au client avec un token d'identification.
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $symfonyToken, $providerKey)
    {
        // Création et envoi d'un token valide au client
        $user = $symfonyToken->getUser();
        $time = time();
        $infos = [];
        $infos[] = $user->getUsername();

        // Si une durée de validité du token est définie, on ajoute le timestamp de la création
        $tokenValidity = $this->container->getParameter("tokenValidity");
        if ($tokenValidity) {
            $infos[] = $time;
        }

        $info = implode($this->container->getParameter("tokenSeparator"), $infos);
        $token = $this->cryptageService->multipleEncryptDecrypt($info, 2, true);
        $response = [
            'user' => $user,
            'token' => $token,
            'time_token' => $time,
        ];

        return new JsonResponse($this->serializer->serialize($response, 'json'), 200, [], true);
    }

    /**
     * Méthode appelée si l'authentification a échouée.
     * On envoie alors un message d'erreur au client.
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        throw $exception;
    }

    /**
     * Méthode appelée si une authentification est requise, mais non reçue, pour éffectuer la requête du client.
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        throw $authException;
    }

    /**
     * Fonctionnalité "remember_me" de Symfony. "false" pour la désactiver.
     * Elle ne doit pas étre activée dans une API.
     * Voir documentation symfony.
     */
    public function supportsRememberMe()
    {
        return false;
    }
}
