<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Exception\EntityNotFoundException;
use App\Exception\ValidationException;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Monolog\Handler\Curl\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use FOS\RestBundle\Request\ParamFetcher;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;


class UtilisateurController extends AbstractFOSRestController
{
    // variables
    private $em;
    private $passwordEncoder;

    public function __construct(EntityManagerInterface $em, UserPasswordEncoderInterface $passwordEncoder
    )
    {
        $this->em = $em;
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * Renvoie une liste d'utilisateurs
     * @return Utilisateur[]
     *
     * @Rest\Get(
     *     path = "/api/utilisateurs",
     *     name = "utilisateurs_list",
     *     options = {"tokenSecurity"}
     * )
     * @OA\Response(
     *      response=200,
     *      description="Retourne les informations d'un ou plusieurs utilisateurs",
     *      @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref=@Model(type=Utilisateur::class, groups={"list"}))
     *      )
     *  )
     *
     * @OA\Response(
     *           response=401,
     *           description="Unauthenticated"
     *      )
     *
     * @OA\Response(
     *           response=500,
     *           description="Autre erreur"
     *      )
     *
     * @OA\Tag(name="Utilisateurs")
     *
     * @Rest\QueryParam(name="nom")
     * @Rest\QueryParam(name="profil")
     * @Rest\QueryParam(name="id")
     * @Rest\View(serializerGroups={"list"})
     */
    public function getWithFilter(ParamFetcherInterface $paramFetcher, Request $request)
    {
        // paramètres du paginator
        $classe = 'Utilisateur';
        $page = $request->get("page");
        $limit = $request->get("limit");
        $orderBy = $request->get("orderBy");
        $filter_text = $request->get("filter_text");
        if ($filter_text == null) {
            $filter_text = '';
        }
        if (!$page || !is_numeric($page)) {
            $page = 1;
        } else {
            $page = intval($page);
        }
        if (!$limit || !is_numeric($limit)) {
            $limit = $this->getParameter('nbPerPage');
        } else {
            $limit = intval($limit);
        }

        // prise en compte des paramètres de filtres standards
        $params = $paramFetcher->all();
        $exceptedParams = [];
        if (array_key_exists('except', $params) && $params['except']) {
            $deserializedParams = json_decode($params['except'], true);
            if ($deserializedParams) {
                $exceptedParams = $deserializedParams;
            } else {
                throw new \UnexpectedValueException('Paramètre except: json invalide');
            }
            unset($params['except']);
        }

        $entities = $this->em->getRepository(Utilisateur::class)->findWithFiltersAndPaginator($page, $limit, $orderBy, $filter_text, $params, $classe, $exceptedParams);
        return $entities;
    }

    /**
     * Crée un utilisateur depuis les données reçues
     * @param Utilisateur $utilisateur
     * @return Utilisateur
     *
     * @Rest\Post(
     *     path = "/api/utilisateurs",
     *     name = "utilisateurs_create",
     * )
     *
     * @OA\Response(
     *            response=201,
     *            description="Retourne les informations de l'utilisateur créé",
     *            @OA\JsonContent(
     *               type="array",
     *               @OA\Items(ref=@Model(type=Utilisateur::class, groups={"list"}))
     *            )
     *        )
     *
     * @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *     )
     *
     * @OA\Response(
     *          response=500,
     *          description="Autre erreur"
     *     )
     *
     * @OA\Tag(name="Utilisateurs")
     * @Rest\View(statusCode = 201, serializerGroups={"detail"})
     * @ParamConverter("utilisateur", converter="fos_rest.request_body")
     */
    public function create(Utilisateur $utilisateur, ConstraintViolationListInterface $validationErrors)
    {
        // Validation des données reçues
        if (count($validationErrors) > 0) {
            throw new ValidationException($validationErrors);
        }

        // Encodez le mot de passe
        $encodedPassword = $this->passwordEncoder->encodePassword($utilisateur, $utilisateur->getPassword());

        // Mettez le mot de passe encodé dans l'objet utilisateur
        $utilisateur->setPassword($encodedPassword);

        $this->em->persist($utilisateur);
        $this->em->flush();

        return $utilisateur;
    }

    /**
     * Met à jour un utilisateur depuis les données reçues
     * @param Utilisateur $utilisateur
     * @param ConstraintViolationListInterface $validationErrors
     * @return Utilisateur
     *
     * @throws ValidationException
     * @Rest\Put(
     *     path = "/api/utilisateurs/{id}",
     *     name = "utilisateurs_update",
     *     requirements = {"id"="\d+"},
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *         response=200,
     *         description="Retourne les informations de l'utilisateur modifié",
     *         @OA\JsonContent(
     *            type="array",
     *            @OA\Items(ref=@Model(type=Utilisateur::class, groups={"list"}))
     *         )
     *     )
     *
     * @OA\Response(
     *       response=401,
     *       description="Unauthenticated"
     *  ),
     * @OA\Response(
     *       response=404,
     *       description="Utilisateur non trouvé"
     *  )
     *
     * @OA\Response(
     *           response=500,
     *           description="Autre erreur"
     *      )
     *
     * @OA\Tag(name="Utilisateurs")
     * @Rest\View(serializerGroups={"detail"})
     * @ParamConverter("utilisateur", converter="fos_rest.request_body")
     */
    public function update(Utilisateur $utilisateur, ParamFetcherInterface $paramFetcher, Request $request, ConstraintViolationListInterface $validationErrors): Utilisateur
    {
        // Validation des données reçues
        if (count($validationErrors) > 0) {
            throw new ValidationException($validationErrors);
        }

        $existingUtilisateur = $this->em->getRepository(Utilisateur::class)->find($request->get('id'));

        if ($existingUtilisateur === null) {
            throw new NotFoundHttpException('L\'utilisateur n\'existe pas');
        } else {
            $encodedPassword = $this->passwordEncoder->encodePassword($utilisateur, $utilisateur->getPassword());

            $utilisateur->getNom() != null ? $existingUtilisateur->setNom($utilisateur->getNom()) : "";
            $utilisateur->getPrenom() != null ? $existingUtilisateur->setPrenom($utilisateur->getPrenom()) : "";
            $utilisateur->getEmail() != null ? $existingUtilisateur->setEmail($utilisateur->getEmail()) : "";
            $utilisateur->getLogin() != null ? $existingUtilisateur->setLogin($utilisateur->getLogin()) : "";
            $utilisateur->getPassword() != null ? $existingUtilisateur->setPassword($encodedPassword) : "";
            $utilisateur->getDatenaissance() != null ? $existingUtilisateur->setDatenaissance($utilisateur->getDatenaissance()) : "";
            $utilisateur->getAdresse() != null ? $existingUtilisateur->setAdresse($utilisateur->getAdresse()) : "";
            $utilisateur->getCodepostal() != null ? $existingUtilisateur->setCodepostal($utilisateur->getCodepostal()) : "";
            $utilisateur->getVille() != null ? $existingUtilisateur->setVille($utilisateur->getVille()) : "";
            $utilisateur->getPays() != null ? $existingUtilisateur->setPays($utilisateur->getPays()) : "";
            $utilisateur->getGenre() != null ? $existingUtilisateur->setGenre($utilisateur->getGenre()) : "";
            $utilisateur->getAntecedents() != null ? $existingUtilisateur->setAntecedents($utilisateur->getAntecedents()) : "";
            $utilisateur->getSociete() != null ? $existingUtilisateur->setSociete($utilisateur->getSociete()) : "";
            $utilisateur->getProfil() != null ? $existingUtilisateur->setProfil($utilisateur->getProfil()) : "";
        }
        $this->em->merge($existingUtilisateur);
        $this->em->flush();

        return $existingUtilisateur;
    }

    /**
     * Supprime un utilisateur depuis les données reçues
     * @param Utilisateur $utilisateur
     * @return string $message de confirmation
     *
     * @Rest\Delete(
     *     path = "/api/utilisateurs/{id}",
     *     name = "utilisateurs_delete",
     *     requirements = {"id"="\d+"},
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *            response="200",
     *            description="Utilisateur supprimé",
     *            content={
     *                @OA\MediaType(
     *                    mediaType="application/json",
     *                    @OA\Schema(
     *                        @OA\Property(
     *                            property="id",
     *                            type="integer",
     *                            description="Identifiant de l'utilisateur supprimé"
     *                        ),
     *                        @OA\Property(
     *                            property="message",
     *                            type="string",
     *                            description="Message de la réponse"
     *                        ),
     *                        example={
     *                            "id": 1,
     *                            "message": "Utilisateur supprimé",
     *                        }
     *                    )
     *                )
     *            }
     *        )
     *
     * @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *    )
     *
     * @OA\Response(
     *         response=404,
     *         description="Utilisateur non trouvé"
     *    )
     *
     *
     * @OA\Tag(name="Utilisateurs")
     * @Rest\View(serializerGroups={"detail"})
     */
    public function delete(Utilisateur $utilisateur)
    {
        // creation de la reponse
        $id = $utilisateur->getId();
        $message = array(
            'id' => $id,
            'message' => 'Utilisateur supprimé',
        );

        // suppression du demande
        $this->em->remove($utilisateur);
        $this->em->flush();

        return $message;
    }

}
