<?php

namespace App\Controller;

use App\Entity\Point;
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


class PointController extends AbstractFOSRestController
{
    // variables
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Renvoie une liste de points
     * @return Point[]
     *
     * @Rest\Get(
     *     path = "/api/points",
     *     name = "points_list",
     *     options = {"tokenSecurity"}
     * )
     * @OA\Response(
     *       response=200,
     *       description="Retourne les informations d'un ou plusieurs points",
     *       @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=Point::class, groups={"list"}))
     *       )
     *   )
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
     *
     * @OA\Tag(name="Points")
     *
     * @Rest\QueryParam(name="titre")
     * @Rest\QueryParam(name="etablissement")
     * @Rest\QueryParam(name="utilisateur")
     * @Rest\QueryParam(name="id")
     * @Rest\View(serializerGroups={"list"})
     */
    public function getWithFilter(ParamFetcherInterface $paramFetcher, Request $request)
    {
        // paramètres du paginator
        $classe = 'Point';
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

        $entities = $this->em->getRepository(Point::class)->findWithFiltersAndPaginator($page, $limit, $orderBy, $filter_text, $params, $classe, $exceptedParams);
        return $entities;
    }

    /**
     * Crée un point depuis les données reçues
     * @param Point $point
     * @return Point
     *
     * @Rest\Post(
     *     path = "/api/points",
     *     name = "points_create",
     *     options = {"tokenSecurity"}
     * )
     * @OA\Response(
     *            response=200,
     *            description="Retourne les informations du point créé",
     *            @OA\JsonContent(
     *               type="array",
     *               @OA\Items(ref=@Model(type=Point::class, groups={"list"}))
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
     * @OA\Tag(name="Points")
     *
     * @Rest\View(statusCode = 201, serializerGroups={"detail"})
     * @ParamConverter("point", converter="fos_rest.request_body")
     */
    public function create(Point $point, ConstraintViolationListInterface $validationErrors)
    {
        // Validation des données reçues
        if (count($validationErrors) > 0) {
            throw new ValidationException($validationErrors);
        }
        $this->em->persist($point);
        $this->em->flush();

        return $point;
    }

    /**
     * Met à jour un point depuis les données reçues
     * @param Point $point
     * @param ConstraintViolationListInterface $validationErrors
     * @return Point
     *
     * @throws ValidationException
     * @Rest\Put(
     *     path = "/api/points/{id}",
     *     name = "points_update",
     *     requirements = {"id"="\d+"},
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *         response=201,
     *         description="Retourne les informations du point modifié",
     *         @OA\JsonContent(
     *            type="array",
     *            @OA\Items(ref=@Model(type=Point::class, groups={"list"}))
     *         )
     *     )
     *
     * @OA\Response(
     *       response=401,
     *       description="Unauthenticated"
     *  ),
     * @OA\Response(
     *       response=404,
     *       description="Point non trouvé"
     *  ),
     *
     * @OA\Response(
     *            response=500,
     *            description="Autre erreur"
     *       )
     *
     * @OA\Tag(name="Points")
     *
     * @Rest\View(serializerGroups={"detail"})
     * @ParamConverter("point", converter="fos_rest.request_body")
     */
    public function update(Point $point, ParamFetcherInterface $paramFetcher, Request $request, ConstraintViolationListInterface $validationErrors): Point
    {
        // Validation des données reçues
        if (count($validationErrors) > 0) {
            throw new ValidationException($validationErrors);
        }

        $existingPoint = $this->em->getRepository(Point::class)->find($request->get('id'));

        if ($existingPoint === null) {
            throw new NotFoundHttpException('Le point n\'existe pas');
        } else {
            $point->getTitre() != null ? $existingPoint->setTitre($point->getTitre()) : "";
            $point->getDescription() != null ? $existingPoint->setDescription($point->getDescription()) : "";
            $point->getCoordonneesGps() != null ? $existingPoint->setCoordonneesGps($point->getCoordonneesGps()) : "";
            $point->getUtilisateur() != null ? $existingPoint->setUtilisateur($point->getUtilisateur()) : "";
            $point->getEtablissement() != null ? $existingPoint->setEtablissement($point->getEtablissement()) : "";
        }
        $this->em->merge($existingPoint);
        $this->em->flush();

        return $existingPoint;
    }

    /**
     * Supprime un point depuis les données reçues
     * @param Point $point
     * @return string $message de confirmation
     *
     * @Rest\Delete(
     *     path = "/api/points/{id}",
     *     name = "points_delete",
     *     requirements = {"id"="\d+"},
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *            response="200",
     *            description="Point supprimé",
     *            content={
     *                @OA\MediaType(
     *                    mediaType="application/json",
     *                    @OA\Schema(
     *                        @OA\Property(
     *                            property="id",
     *                            type="integer",
     *                            description="Identifiant du point supprimé"
     *                        ),
     *                        @OA\Property(
     *                            property="message",
     *                            type="string",
     *                            description="Message de la réponse"
     *                        ),
     *                        example={
     *                            "id": 1,
     *                            "message": "Point supprimé",
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
     *         description="Point non trouvé"
     *    )
     *
     * @OA\Tag(name="Points")
     *
     * @Rest\View(serializerGroups={"detail"})
     */
    public function delete(Point $point)
    {
        // creation de la reponse
        $id = $point->getId();
        $message = array(
            'id' => $id,
            'message' => 'Point supprimé',
        );

        // suppression du demande
        $this->em->remove($point);
        $this->em->flush();

        return $message;
    }

}
