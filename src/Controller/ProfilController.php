<?php

namespace App\Controller;

use App\Entity\Profil;
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

class ProfilController extends AbstractFOSRestController
{
    // variables
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Renvoie une liste de profils
     * @return Profil[]
     *
     * @Rest\Get(
     *     path = "/api/profils",
     *     name = "profils_list",
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *        response=200,
     *        description="Retourne les informations d'un ou plusieurs profils",
     *        @OA\JsonContent(
     *           type="array",
     *           @OA\Items(ref=@Model(type=Profil::class, groups={"list"}))
     *        )
     *    )
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
     * @OA\Tag(name="Profils")
     *
     * @Rest\QueryParam(name="id")
     * @Rest\QueryParam(name="nom")
     * @Rest\QueryParam(name="description")
     * @Rest\View(serializerGroups={"list"})
     */
    public function getWithFilter(ParamFetcherInterface $paramFetcher, Request $request)
    {
        // paramètres du paginator
        $classe = 'Profil';
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

        $entities = $this->em->getRepository(Profil::class)->findWithFiltersAndPaginator($page, $limit, $orderBy, $filter_text, $params, $classe, $exceptedParams);
        return $entities;
    }

    /**
     * Crée un profil depuis les données reçues
     * @param Profil $profil
     * @return Profil
     *
     * @Rest\Post(
     *     path = "/api/profils",
     *     name = "profils_create",
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *            response=201,
     *            description="Retourne les informations du profil créé",
     *            @OA\JsonContent(
     *               type="array",
     *               @OA\Items(ref=@Model(type=Profil::class, groups={"list"}))
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
     * @OA\Tag(name="Profils")
     * @Rest\View(statusCode = 201, serializerGroups={"detail"})
     * @ParamConverter("profil", converter="fos_rest.request_body")
     */
    public function create(Profil $profil, ConstraintViolationListInterface $validationErrors)
    {
        // Validation des données reçues
        if (count($validationErrors) > 0) {
            throw new ValidationException($validationErrors);
        }
        $this->em->persist($profil);
        $this->em->flush();

        return $profil;
    }

    /**
     * Met à jour un profil depuis les données reçues
     * @param Profil $profil
     * @param ConstraintViolationListInterface $validationErrors
     * @return Profil
     *
     * @throws ValidationException
     * @Rest\Put(
     *     path = "/api/profils/{id}",
     *     name = "profils_update",
     *     requirements = {"id"="\d+"},
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *         response=200,
     *         description="Retourne les informations du profil modifié",
     *         @OA\JsonContent(
     *            type="array",
     *            @OA\Items(ref=@Model(type=Profil::class, groups={"list"}))
     *         )
     *     )
     *
     * @OA\Response(
     *       response=401,
     *       description="Unauthenticated"
     *  ),
     * @OA\Response(
     *       response=404,
     *       description="Profil non trouvé"
     *  )
     *
     *
     * @OA\Response(
     *           response=500,
     *           description="Autre erreur"
     *      )
     *
     * @OA\Tag(name="Profils")
     * @Rest\View(serializerGroups={"detail"})
     * @ParamConverter("profil", converter="fos_rest.request_body")
     */
    public function update(Profil $profil, ParamFetcherInterface $paramFetcher, Request $request, ConstraintViolationListInterface $validationErrors): Profil
    {
        // Validation des données reçues
        if (count($validationErrors) > 0) {
            throw new ValidationException($validationErrors);
        }

        $existingProfil = $this->em->getRepository(Profil::class)->find($request->get('id'));

        if ($existingProfil === null) {
            throw new NotFoundHttpException('Le profil n\'existe pas');
        } else {
            $profil->getNom() != null ? $existingProfil->setNom($profil->getNom()) : "";
            $profil->getDescription() != null ? $existingProfil->setDescription($profil->getDescription()) : "";
        }
        $this->em->merge($existingProfil);
        $this->em->flush();

        return $existingProfil;
    }

    /**
     * Supprime un profil depuis les données reçues
     * @param Profil $profil
     * @return string $message de confirmation
     *
     * @Rest\Delete(
     *     path = "/api/profils/{id}",
     *     name = "profils_delete",
     *     requirements = {"id"="\d+"},
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *            response="200",
     *            description="Profil supprimé",
     *            content={
     *                @OA\MediaType(
     *                    mediaType="application/json",
     *                    @OA\Schema(
     *                        @OA\Property(
     *                            property="id",
     *                            type="integer",
     *                            description="Identifiant du profil supprimé"
     *                        ),
     *                        @OA\Property(
     *                            property="message",
     *                            type="string",
     *                            description="Message de la réponse"
     *                        ),
     *                        example={
     *                            "id": 1,
     *                            "message": "Profil supprimé",
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
     *         description="Profil non trouvé"
     *    )
     *
     * @OA\Tag(name="Profils")
     * @Rest\View(serializerGroups={"detail"})
     */
    public function delete(Profil $profil)
    {
        // creation de la reponse
        $id = $profil->getId();
        $message = array(
            'id' => $id,
            'message' => 'Profil supprimé',
        );

        // suppression du demande
        $this->em->remove($profil);
        $this->em->flush();

        return $message;
    }

}
