<?php

namespace App\Controller;

use App\Entity\Societe;
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


class SocieteController extends AbstractFOSRestController
{
    // variables
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Renvoie une liste de sociétés
     * @return Societe[]
     *
     * @Rest\Get(
     *     path = "/api/societes",
     *     name = "societes_list",
     *     options = {"tokenSecurity"}
     * )
     * @OA\Response(
     *        response=200,
     *        description="Retourne les informations d'une ou plusieurs sociétés",
     *        @OA\JsonContent(
     *           type="array",
     *           @OA\Items(ref=@Model(type=Societe::class, groups={"list"}))
     *        )
     *    )
     *
     * @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *    )
     *
     * @OA\Response(
     *         response=500,
     *         description="Autre erreur"
     *    )
     *
     * @OA\Tag(name="Societes")
     * @Rest\QueryParam(name="id")
     * @Rest\QueryParam(name="nom")
     * @Rest\QueryParam(name="adresse")
     * @Rest\QueryParam(name="ville")
     * @Rest\QueryParam(name="codepostal")
     * @Rest\QueryParam(name="telephone")
     * @Rest\QueryParam(name="pays")
     * @Rest\QueryParam(name="siren")
     * @Rest\View(serializerGroups={"list"})
     */
    public function getWithFilter(ParamFetcherInterface $paramFetcher, Request $request)
    {
        // paramètres du paginator
        $classe = 'Societe';
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

        $entities = $this->em->getRepository(Societe::class)->findWithFiltersAndPaginator($page, $limit, $orderBy, $filter_text, $params, $classe, $exceptedParams);
        return $entities;
    }

    /**
     * Crée une société depuis les données reçues
     * @param Societe $societe
     * @return Societe
     *
     * @Rest\Post(
     *     path = "/api/societes",
     *     name = "societes_create",
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *          response=201,
     *          description="Retourne les informations de la société créée",
     *          @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref=@Model(type=Societe::class, groups={"list"}))
     *          )
     *      )
     *
     * @OA\Response(
     *        response=401,
     *        description="Unauthenticated"
     *   )
     *
     * @OA\Response(
     *        response=500,
     *        description="Autre erreur"
     *   )
     *
     * @OA\Tag(name="Societes")
     * @Rest\View(statusCode = 201, serializerGroups={"detail"})
     * @ParamConverter("societe", converter="fos_rest.request_body")
     */
    public function create(Societe $societe, ConstraintViolationListInterface $validationErrors)
    {
        // Validation des données reçues
        if (count($validationErrors) > 0) {
            throw new ValidationException($validationErrors);
        }
        $this->em->persist($societe);
        $this->em->flush();

        return $societe;
    }

    /**
     * Met à jour une société depuis les données reçues
     * @param Societe $societe
     * @param ConstraintViolationListInterface $validationErrors
     * @return Societe
     *
     * @throws ValidationException
     * @Rest\Put(
     *     path = "/api/societes/{id}",
     *     name = "societes_update",
     *     requirements = {"id"="\d+"},
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *         response=200,
     *         description="Retourne les informations de la société modifiée",
     *         @OA\JsonContent(
     *            type="array",
     *            @OA\Items(ref=@Model(type=Societe::class, groups={"list"}))
     *         )
     *     )
     *
     * @OA\Response(
     *       response=401,
     *       description="Unauthenticated"
     *  )
     * @OA\Response(
     *       response=404,
     *       description="Société non trouvée"
     *  )
     *
     * @OA\Response(
     *         response=500,
     *         description="Autre erreur"
     *    )
     *
     * @OA\Tag(name="Societes")
     * @Rest\View(serializerGroups={"detail"})
     * @ParamConverter("societe", converter="fos_rest.request_body")
     */
    public function update(Societe $societe, ParamFetcherInterface $paramFetcher, Request $request, ConstraintViolationListInterface $validationErrors): Societe
    {
        // Validation des données reçues
        if (count($validationErrors) > 0) {
            throw new ValidationException($validationErrors);
        }

        $existingSociete = $this->em->getRepository(Societe::class)->find($request->get('id'));

        if ($existingSociete === null) {
            throw new NotFoundHttpException('Le societe n\'existe pas');
        } else {
            $societe->getNom() != null ? $existingSociete->setNom($societe->getNom()) : "";
            $societe->getAdresse() != null ? $existingSociete->setAdresse($societe->getAdresse()) : "";
            $societe->getVille() != null ? $existingSociete->setVille($societe->getVille()) : "";
            $societe->getCodepostal() != null ? $existingSociete->setCodepostal($societe->getCodepostal()) : "";
            $societe->getTelephone() != null ? $existingSociete->setTelephone($societe->getTelephone()) : "";
            $societe->getPays() != null ? $existingSociete->setPays($societe->getPays()) : "";
            $societe->getSiren() != null ? $existingSociete->setSiren($societe->getSiren()) : "";
        }
        $this->em->merge($existingSociete);
        $this->em->flush();

        return $existingSociete;
    }

    /**
     * Supprime une société depuis les données reçues
     * @param Societe $societe
     * @return string $message de confirmation
     *
     * @Rest\Delete(
     *     path = "/api/societes/{id}",
     *     name = "societes_delete",
     *     requirements = {"id"="\d+"},
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *            response="200",
     *            description="Société supprimée",
     *            content={
     *                @OA\MediaType(
     *                    mediaType="application/json",
     *                    @OA\Schema(
     *                        @OA\Property(
     *                            property="id",
     *                            type="integer",
     *                            description="Identifiant de la societé supprimée"
     *                        ),
     *                        @OA\Property(
     *                            property="message",
     *                            type="string",
     *                            description="Message de la réponse"
     *                        ),
     *                        example={
     *                            "id": 1,
     *                            "message": "Société supprimée",
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
     *         description="Société non trouvée"
     *    )
     *
     * @OA\Tag(name="Societes")
     * @Rest\View(serializerGroups={"detail"})
     */
    public function delete(Societe $societe)
    {
        // creation de la reponse
        $id = $societe->getId();
        $message = array(
            'id' => $id,
            'message' => 'Societe supprimé',
        );

        // suppression du demande
        $this->em->remove($societe);
        $this->em->flush();

        return $message;
    }

}
