<?php

namespace App\Controller;

use App\Entity\Trajet;
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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;


class TrajetController extends AbstractFOSRestController
{
    // variables
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Renvoie une liste de trajets
     * @return Trajet[]
     *
     * @Rest\Get(
     *     path = "/api/trajets",
     *     name = "trajets_list",
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *         response=200,
     *         description="Retourne les informations d'un ou plusieurs trajets",
     *         @OA\JsonContent(
     *            type="array",
     *            @OA\Items(ref=@Model(type=Trajet::class, groups={"list"}))
     *         )
     *     )
     *
     * @OA\Response(
     *        response=401,
     *        description="Unauthenticated"
     *   )
     *
     * @OA\Response(
     *           response=500,
     *           description="Autre erreur"
     *      )
     *
     * @OA\Tag(name="Trajets")
     *
     * @Rest\QueryParam(name="nom")
     * @Rest\QueryParam(name="profil")
     * @Rest\QueryParam(name="id")
     * @Rest\View(serializerGroups={"list"})
     */
    public function getWithFilter(ParamFetcherInterface $paramFetcher, Request $request)
    {
        // paramètres du paginator
        $classe = 'Trajet';
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

        $entities = $this->em->getRepository(Trajet::class)->findWithFiltersAndPaginator($page, $limit, $orderBy, $filter_text, $params, $classe, $exceptedParams);
        return $entities;
    }

    /**
     * Crée un trajet depuis les données reçues
     * @param Trajet $trajet
     * @return Trajet
     *
     * @Rest\Post(
     *     path = "/api/trajets",
     *     name = "trajets_create",
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *            response=200,
     *            description="Retourne les informations du trajet créé",
     *            @OA\JsonContent(
     *               type="array",
     *               @OA\Items(ref=@Model(type=Trajet::class, groups={"list"}))
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
     *
     * @OA\Tag(name="Trajets")
     * @Rest\View(statusCode = 201, serializerGroups={"detail"})
     * @ParamConverter("trajet", converter="fos_rest.request_body")
     */
    public function create(Trajet $trajet, ConstraintViolationListInterface $validationErrors)
    {
        // Validation des données reçues
        if (count($validationErrors) > 0) {
            throw new ValidationException($validationErrors);
        }
        $this->em->persist($trajet);
        $this->em->flush();

        return $trajet;
    }

    /**
     * Met à jour un trajet depuis les données reçues
     * @param Trajet $trajet
     * @param ConstraintViolationListInterface $validationErrors
     * @return Trajet
     *
     * @throws ValidationException
     * @Rest\Put(
     *     path = "/api/trajets/{id}",
     *     name = "trajets_update",
     *     requirements = {"id"="\d+"},
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *         response=200,
     *         description="Retourne les informations du trajet modifié",
     *         @OA\JsonContent(
     *            type="array",
     *            @OA\Items(ref=@Model(type=Trajet::class, groups={"list"}))
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
     *       description="Trajet non trouvé"
     *  )
     *
     * @OA\Response(
     *           response=500,
     *           description="Autre erreur"
     *      )
     *
     * @OA\Tag(name="Trajets")
     * @Rest\View(serializerGroups={"detail"})
     * @ParamConverter("trajet", converter="fos_rest.request_body")
     */
    public function update(Trajet $trajet, ParamFetcherInterface $paramFetcher, Request $request, ConstraintViolationListInterface $validationErrors): Trajet
    {
        // Validation des données reçues
        if (count($validationErrors) > 0) {
            throw new ValidationException($validationErrors);
        }

        $existingTrajet = $this->em->getRepository(Trajet::class)->find($request->get('id'));

        if ($existingTrajet === null) {
            throw new NotFoundHttpException('Le trajet n\'existe pas');
        } else {
            $trajet->getAmbulancier() != null ? $existingTrajet->setAmbulancier($trajet->getAmbulancier()) : "";
            $trajet->getReservation() != null ? $existingTrajet->setReservation($trajet->getReservation()) : "";
            $trajet->getVoiture() != null ? $existingTrajet->setVoiture($trajet->getVoiture()) : "";
        }
        $this->em->merge($existingTrajet);
        $this->em->flush();

        return $existingTrajet;
    }

    /**
     * Supprime un trajet depuis les données reçues
     * @param Trajet $trajet
     * @return string $message de confirmation
     *
     * @Rest\Delete(
     *     path = "/api/trajets/{id}",
     *     name = "trajets_delete",
     *     requirements = {"id"="\d+"},
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *            response="200",
     *            description="Trajet supprimé",
     *            content={
     *                @OA\MediaType(
     *                    mediaType="application/json",
     *                    @OA\Schema(
     *                        @OA\Property(
     *                            property="id",
     *                            type="integer",
     *                            description="Identifiant du trajet supprimé"
     *                        ),
     *                        @OA\Property(
     *                            property="message",
     *                            type="string",
     *                            description="Message de la réponse"
     *                        ),
     *                        example={
     *                            "id": 1,
     *                            "message": "Trajet supprimé",
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
     *         description="Trajet non trouvé"
     *    )
     *
     * @OA\Tag(name="Trajets")
     * @Rest\View(serializerGroups={"detail"})
     */
    public function delete(Trajet $trajet)
    {
        // creation de la reponse
        $id = $trajet->getId();
        $message = array(
            'id' => $id,
            'message' => 'Trajet supprimé',
        );

        // suppression du demande
        $this->em->remove($trajet);
        $this->em->flush();

        return $message;
    }

}
