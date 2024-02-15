<?php

namespace App\Security;

use App\Entity\Utilisateur;
use App\Service\CryptageService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TokenAuthenticator extends AbstractGuardAuthenticator
{
    private $em;
    private $cryptageService;
    private $container;

    public function __construct(
        EntityManagerInterface $em,
        ContainerInterface $container,
        CryptageService $cryptageService
    ) {
        $this->em = $em;
        $this->cryptageService = $cryptageService;
        $this->container = $container;
    }

    /**
     * Méthode appelée à chaque requête pour savoir si le tokenAuthenticator doit s'activer.
     * Si on retourne "false", il n'est pas activé et on laisse passer la requête.
     */
    public function supports(Request $request)
    {
        // On vérifie que la tokenSecurity est activée
        if (!$this->container->getParameter('tokenSecurity')) {
            return false;
        }

        if ($request->attributes->get('_controller') != "nelmio_api_doc.controller.swagger_ui") {
            // On vérifie si la méthode du controller à atteindre est sécurisée (On lie les annotations de la méthode)
            list($controllerService, $controllerMethod) = explode('::', $request->attributes->get('_controller'));
            $controllerObject = $this->container->get($controllerService);
            $reflectionObject = new \ReflectionObject($controllerObject);
            $reflectionMethod = $reflectionObject->getMethod($controllerMethod);
            $annotationReader = new AnnotationReader();
            $methodAnnotation = $annotationReader->getMethodAnnotation($reflectionMethod, 'Symfony\Component\Routing\Annotation\Route');

            if (in_array('tokenSecurity', $methodAnnotation->getOptions())) {
                return true;
            } else {
                return false;
            }
        }
        else {
            return false;
        }
    }

    /**
     * Méthode appelée à chaque requête pour récupérer le token envoyé par l'utilisateur.
     * Ils sont alors transmis à getUser().
     */
    public function getCredentials(Request $request)
    {
        $token = $request->headers->get('X-AUTH-TOKEN');
        if (!$token && $request->attributes->get('_controller') != "nelmio_api_doc.controller.swagger_ui") {
            throw new AuthenticationException("Authentification requise");
        } else if ($request->attributes->get('_controller') == "nelmio_api_doc.controller.swagger_ui") {
            return true;
        }
        return $token;
    }

    /**
     * Récupère l'utilisateur depuis la base de données à partir de son login
     */
    public function getUser($token, UserProviderInterface $userProvider)
    {
            // Décryptage du token
            $tokenSeparator = $this->container->getParameter('tokenSeparator');
            $tokenValidity = $this->container->getParameter('tokenValidity');

            $decryptedToken = $this->cryptageService->multipleEncryptDecrypt($token, 2);
            if (!$decryptedToken) {
                throw new AuthenticationException('Token invalide');
            }
            $userInfos = explode($tokenSeparator, $decryptedToken);

            // Vérification de la validité du token
            if ($tokenValidity) {
                if (count($userInfos) < 2 || !is_numeric($userInfos[1])) {
                    throw new AuthenticationException('Token invalide : timestamp invalide');
                }
                $timestamp = intval($userInfos[1]);
                $today = time();
                if ($today - $timestamp >= $tokenValidity) {
                    throw new AuthenticationException('Temps de connexion dépassé. Veuillez vous reconnecter');
                }
            }

            // Si un utilisateur est trouvé, on passe à la méthode checkCredentials()
            return $this->em->getRepository(Utilisateur::class)->findOneBy(['login' => $userInfos[0]]);
    }

    /**
     * Méthode pour vérifier que le mot de passe est valide
     * Dans le cas d'un token API, cette vérification n'est pas nécéssaire
     * On retourne true pour valider l'authentification
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    /**
     * Méthode appelée si l'authentification est un succés.
     * On retourne null pour laisser passer la requête
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return null;
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
