<?php

namespace App\Controller;

use App\Entity\Reservation;
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


class ReservationController extends AbstractFOSRestController
{
    // variables
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Renvoie une liste de réservations
     * @return Reservation[]
     *
     * @Rest\Get(
     *     path = "/api/reservations",
     *     name = "reservations_list",
     *     options = {"tokenSecurity"}
     * )
     * @OA\Response(
     *        response=200,
     *        description="Retourne les informations d'une ou plusieurs réservations",
     *        @OA\JsonContent(
     *           type="array",
     *           @OA\Items(ref=@Model(type=Reservation::class, groups={"list"}))
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
     * @OA\Tag(name="Reservations")
     *
     * @Rest\QueryParam(name="nom")
     * @Rest\QueryParam(name="profil")
     * @Rest\QueryParam(name="id")
     * @Rest\QueryParam(name="utilisateur")
     * @Rest\View(serializerGroups={"list"})
     */
    public function getWithFilter(ParamFetcherInterface $paramFetcher, Request $request)
    {
        // paramètres du paginator
        $classe = 'Reservation';
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

        $entities = $this->em->getRepository(Reservation::class)->findWithFiltersAndPaginator($page, $limit, $orderBy, $filter_text, $params, $classe, $exceptedParams);
        return $entities;
    }

    /**
     * Crée une réservation depuis les données reçues
     * @param Reservation $reservation
     * @return Reservation
     *
     * @Rest\Post(
     *     path = "/api/reservations",
     *     name = "reservations_create",
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *            response=201,
     *            description="Retourne les informations de la réservation créée",
     *            @OA\JsonContent(
     *               type="array",
     *               @OA\Items(ref=@Model(type=Reservation::class, groups={"list"}))
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
     * @OA\Tag(name="Reservations")
     *
     * @Rest\View(statusCode = 201, serializerGroups={"detail"})
     * @ParamConverter("reservation", converter="fos_rest.request_body")
     */
    public function create(Reservation $reservation, ConstraintViolationListInterface $validationErrors)
    {
        // Validation des données reçues
        if (count($validationErrors) > 0) {
            throw new ValidationException($validationErrors);
        }
        $this->em->persist($reservation);
        $this->em->flush();

        return $reservation;
    }

    /**
     * Met à jour une réservation depuis les données reçues
     * @param Reservation $reservation
     * @param ConstraintViolationListInterface $validationErrors
     * @return Reservation
     *
     * @throws ValidationException
     * @Rest\Put(
     *     path = "/api/reservations/{id}",
     *     name = "reservations_update",
     *     requirements = {"id"="\d+"},
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *         response=201,
     *         description="Retourne les informations de la réservation modifiée",
     *         @OA\JsonContent(
     *            type="array",
     *            @OA\Items(ref=@Model(type=Reservation::class, groups={"list"}))
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
     *       description="Réservatuib non trouvée"
     *  )
     *
     * @OA\Response(
     *           response=500,
     *           description="Autre erreur"
     *      )
     *
     * @OA\Tag(name="Reservations")
     *
     * @Rest\View(serializerGroups={"detail"})
     * @ParamConverter("reservation", converter="fos_rest.request_body")
     */
    public function update(Reservation $reservation, ParamFetcherInterface $paramFetcher, Request $request, ConstraintViolationListInterface $validationErrors): Reservation
    {
        // Validation des données reçues
        if (count($validationErrors) > 0) {
            throw new ValidationException($validationErrors);
        }

        $existingReservation = $this->em->getRepository(Reservation::class)->find($request->get('id'));

        if ($existingReservation === null) {
            throw new NotFoundHttpException('L\'reservation n\'existe pas');
        } else {
            $reservation->getDateRdv() != null ? $existingReservation->setDateRdv($reservation->getDateRdv()) : "";
            $reservation->getBontransport() != null ? $existingReservation->setBontransport($reservation->getBontransport()) : "";
            $reservation->getTypeSejour() != null ? $existingReservation->setTypeSejour($reservation->getTypeSejour()) : "";
            $reservation->getEtat() != null ? $existingReservation->setEtat($reservation->getEtat()) : "";
            $reservation->getEtablissement() != null ? $existingReservation->setEtablissement($reservation->getEtablissement()) : "";
            $reservation->getUtilisateur() != null ? $existingReservation->setUtilisateur($reservation->getUtilisateur()) : "";
            $reservation->getSociete() != null ? $existingReservation->setSociete($reservation->getSociete()) : "";
        }
        $this->em->merge($existingReservation);
        $this->em->flush();

        return $existingReservation;
    }

    /**
     * Supprime une réservation depuis les données reçues
     * @param Reservation $reservation
     * @return string $message de confirmation
     *
     * @Rest\Delete(
     *     path = "/api/reservations/{id}",
     *     name = "reservations_delete",
     *     requirements = {"id"="\d+"},
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *            response="200",
     *            description="Réservation supprimée",
     *            content={
     *                @OA\MediaType(
     *                    mediaType="application/json",
     *                    @OA\Schema(
     *                        @OA\Property(
     *                            property="id",
     *                            type="integer",
     *                            description="Identifiant de la réservation supprimée"
     *                        ),
     *                        @OA\Property(
     *                            property="message",
     *                            type="string",
     *                            description="Message de la réponse"
     *                        ),
     *                        example={
     *                            "id": 1,
     *                            "message": "Réservation supprimée",
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
     *         description="Réservation non trouvée"
     *    )
     *
     * @OA\Tag(name="Reservations")
     *
     * @Rest\View(serializerGroups={"detail"})
     */
    public function delete(Reservation $reservation)
    {
        // creation de la reponse
        $id = $reservation->getId();
        $message = array(
            'id' => $id,
            'message' => 'Reservation supprimé',
        );

        // suppression du demande
        $this->em->remove($reservation);
        $this->em->flush();

        return $message;
    }

}
