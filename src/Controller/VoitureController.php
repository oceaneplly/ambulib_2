<?php

namespace App\Controller;

use App\Entity\Voiture;
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


class VoitureController extends AbstractFOSRestController
{
    // variables
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Renvoie une liste de voitures
     * @return Voiture[]
     *
     * @Rest\Get(
     *     path = "/api/voitures",
     *     name = "voitures_list",
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *         response=200,
     *         description="Retourne les informations d'une ou plusieurs voitures",
     *         @OA\JsonContent(
     *            type="array",
     *            @OA\Items(ref=@Model(type=Voiture::class, groups={"list"}))
     *         )
     *     )
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
     * @OA\Tag(name="Voitures")
     *
     * @Rest\QueryParam(name="nom")
     * @Rest\QueryParam(name="profil")
     * @Rest\QueryParam(name="id")
     * @Rest\View(serializerGroups={"list"})
     */
    public function getWithFilter(ParamFetcherInterface $paramFetcher, Request $request)
    {
        // paramètres du paginator
        $classe = 'Voiture';
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

        $entities = $this->em->getRepository(Voiture::class)->findWithFiltersAndPaginator($page, $limit, $orderBy, $filter_text, $params, $classe, $exceptedParams);
        return $entities;
    }

    /**
     * Crée une voiture depuis les données reçues
     * @param Voiture $voiture
     * @return Voiture
     *
     * @Rest\Post(
     *     path = "/api/voitures",
     *     name = "voitures_create",
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *            response=201,
     *            description="Retourne les informations de la voiture créée",
     *            @OA\JsonContent(
     *               type="array",
     *               @OA\Items(ref=@Model(type=Voiture::class, groups={"list"}))
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
     * @OA\Tag(name="Voitures")
     * @Rest\View(statusCode = 201, serializerGroups={"detail"})
     * @ParamConverter("voiture", converter="fos_rest.request_body")
     */
    public function create(Voiture $voiture, ConstraintViolationListInterface $validationErrors)
    {
        // Validation des données reçues
        if (count($validationErrors) > 0) {
            throw new ValidationException($validationErrors);
        }
        $this->em->persist($voiture);
        $this->em->flush();

        return $voiture;
    }

    /**
     * Met à jour une voiture depuis les données reçues
     * @param Voiture $voiture
     * @param ConstraintViolationListInterface $validationErrors
     * @return Voiture
     *
     * @throws ValidationException
     * @Rest\Put(
     *     path = "/api/voitures/{id}",
     *     name = "voitures_update",
     *     requirements = {"id"="\d+"},
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *         response=200,
     *         description="Retourne les informations de la voiture modifiée",
     *         @OA\JsonContent(
     *            type="array",
     *            @OA\Items(ref=@Model(type=Voiture::class, groups={"list"}))
     *         )
     *     )
     *
     * @OA\Response(
     *       response=401,
     *       description="Unauthenticated"
     *  )
     *
     * @OA\Response(
     *       response=404,
     *       description="Voiture non trouvée"
     *  )
     * @OA\Response(
     *           response=500,
     *           description="Autre erreur"
     *      )
     *
     * @OA\Tag(name="Voitures")
     * @Rest\View(serializerGroups={"detail"})
     * @ParamConverter("voiture", converter="fos_rest.request_body")
     */
    public function update(Voiture $voiture, ParamFetcherInterface $paramFetcher, Request $request, ConstraintViolationListInterface $validationErrors): Voiture
    {
        // Validation des données reçues
        if (count($validationErrors) > 0) {
            throw new ValidationException($validationErrors);
        }

        $existingVoiture = $this->em->getRepository(Voiture::class)->find($request->get('id'));

        if ($existingVoiture === null) {
            throw new NotFoundHttpException('L\'voiture n\'existe pas');
        } else {
            $voiture->getSociete() != null ? $existingVoiture->setSociete($voiture->getSociete()) : "";
            $voiture->getImmatriculation() != null ? $existingVoiture->setImmatriculation($voiture->getImmatriculation()) : "";
            $voiture->getMarque() != null ? $existingVoiture->setMarque($voiture->getMarque()) : "";
            $voiture->getModele() != null ? $existingVoiture->setModele($voiture->getModele()) : "";
            $voiture->getAnnee() != null ? $existingVoiture->setAnnee($voiture->getAnnee()) : "";
            $voiture->getEmplacement() != null ? $existingVoiture->setEmplacement($voiture->getEmplacement()) : "";
            $voiture->getTypeVoiture() != null ? $existingVoiture->setTypeVoiture($voiture->getTypeVoiture()) : "";
            $voiture->getEtat() != null ? $existingVoiture->setEtat($voiture->getEtat()) : "";

        }
        $this->em->merge($existingVoiture);
        $this->em->flush();

        return $existingVoiture;
    }

    /**
     * Supprime une voiture depuis les données reçues
     * @param Voiture $voiture
     * @return string $message de confirmation
     *
     * @Rest\Delete(
     *     path = "/api/voitures/{id}",
     *     name = "voitures_delete",
     *     requirements = {"id"="\d+"},
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *            response="200",
     *            description="Voiture supprimée",
     *            content={
     *                @OA\MediaType(
     *                    mediaType="application/json",
     *                    @OA\Schema(
     *                        @OA\Property(
     *                            property="id",
     *                            type="integer",
     *                            description="Identifiant de la voiture supprimée"
     *                        ),
     *                        @OA\Property(
     *                            property="message",
     *                            type="string",
     *                            description="Message de la réponse"
     *                        ),
     *                        example={
     *                            "id": 1,
     *                            "message": "Voiture supprimée",
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
     *         description="Voiture non trouvée"
     *    )
     *
     * @OA\Tag(name="Voitures")
     * @Rest\View(serializerGroups={"detail"})
     */
    public function delete(Voiture $voiture)
    {
        // creation de la reponse
        $id = $voiture->getId();
        $message = array(
            'id' => $id,
            'message' => 'Voiture supprimé',
        );

        // suppression du demande
        $this->em->remove($voiture);
        $this->em->flush();

        return $message;
    }

}
