<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;

use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

/**
 * Controller permettant l'authentification
 *
 * @author Océane Pouilly
 */
class LoginController extends AbstractController
{
    /**
     * Sert à créer une route pour s'authentifier, interceptée par le loginAuthenticator
     *
     * @Rest\Post(
     *     path = "/login",
     *     name = "app_login"
     * )
     *
     * @OA\Response(
     *             response="200",
     *             description="Utilisateur authentifié",
     *             content={
     *                 @OA\MediaType(
     *                     mediaType="application/json",
     *                     @OA\Schema(
     *                         @OA\Property(
     *                           property="user",
     *                           type="array",
     *                           @OA\Items(ref=@Model(type=Utilisateur::class, groups={"list"}))
     *                          ),
     *                         @OA\Property(
     *                             property="token",
     *                             type="string",
     *                             description="Token renvoyé par la requête"
     *                         ),
     *                         @OA\Property(
     *                              property="time_token",
     *                              type="integer",
     *                              description="Temps de validité"
     *                          )
     *                     )
     *                 )
     *             }
     *         )
     *
     * @OA\Response(
     *            response=500,
     *            description="Autre erreur"
     *       )
     *
     * @OA\Tag(name="Login")
     * @Rest\View(serializerGroups={"login"})
     */
    public function login(): Response
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the loginAuthenticator.');
    }

    /**
     * Renvoie un utilisateur depuis son id
     * @return bool[]
     *
     * @Rest\Get(
     *     path = "/tokenValidity",
     *     name = "token_validity_check",
     *     options = {"tokenSecurity"}
     * )
     * @Rest\View()
     */
    public function checkTokenValidity()
    {
        // La vérification du token se fait par le TokenAuthenticator.
        // Si la requête arrive jusqu'ici, alors, le token était valide.
        return ['tokenValidity' => true];
    }
}
