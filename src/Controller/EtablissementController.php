<?php

namespace App\Controller;

use App\Entity\Etablissement;
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


class EtablissementController extends AbstractFOSRestController
{
    // variables
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Renvoie une liste d'établissements
     * @return Etablissement[]
     *
     * @Rest\Get(
     *     path = "/api/etablissements",
     *     name = "etablissements_list",
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *       response=200,
     *       description="Retourne les informations d'un ou plusieurs établissements",
     *       @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=Etablissement::class, groups={"list"}))
     *       )
     *   )
     *
     * @OA\Response(
     *        response=401,
     *        description="Unauthenticated"
     *   )
     *
     * @OA\Response(
     *            response=500,
     *            description="Autre erreur"
     *       )
     *
     *
     * @OA\Tag(name="Etablissements")
     *
     * @Rest\QueryParam(name="nom")
     * @Rest\QueryParam(name="adresse")
     * @Rest\QueryParam(name="id")
     * @Rest\QueryParam(name="ville")
     * @Rest\QueryParam(name="codepostal")
     * @Rest\QueryParam(name="telephone")
     * @Rest\QueryParam(name="pays")
     * @Rest\View(serializerGroups={"list"})
     */
    public function getWithFilter(ParamFetcherInterface $paramFetcher, Request $request)
    {
        // paramètres du paginator
        $classe = 'Etablissement';
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

        $entities = $this->em->getRepository(Etablissement::class)->findWithFiltersAndPaginator($page, $limit, $orderBy, $filter_text, $params, $classe, $exceptedParams);
        return $entities;
    }

    /**
     * Crée un établissement depuis les données reçues
     * @param Etablissement $etablissement
     * @return Etablissement
     *
     * @Rest\Post(
     *     path = "/api/etablissements",
     *     name = "etablissements_create",
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *            response=201,
     *            description="Retourne les informations de l'établissement créé",
     *            @OA\JsonContent(
     *               type="array",
     *               @OA\Items(ref=@Model(type=Etablissement::class, groups={"list"}))
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
     * @OA\Tag(name="Etablissements")
     * @Rest\View(statusCode = 201, serializerGroups={"detail"})
     * @ParamConverter("etablissement", converter="fos_rest.request_body")
     */
    public function create(Etablissement $etablissement, ConstraintViolationListInterface $validationErrors)
    {
        // Validation des données reçues
        if (count($validationErrors) > 0) {
            throw new ValidationException($validationErrors);
        }
        $this->em->persist($etablissement);
        $this->em->flush();

        return $etablissement;
    }

    /**
     * Met à jour un établissement depuis les données reçues
     * @param Etablissement $etablissement
     * @param ConstraintViolationListInterface $validationErrors
     * @return Etablissement
     *
     * @throws ValidationException
     * @Rest\Put(
     *     path = "/api/etablissements/{id}",
     *     name = "etablissements_update",
     *     requirements = {"id"="\d+"},
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *         response=200,
     *         description="Retourne les informations de l'établissement modifié",
     *         @OA\JsonContent(
     *            type="array",
     *            @OA\Items(ref=@Model(type=Etablissement::class, groups={"list"}))
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
     *       description="Etablissement non trouvé"
     *  )
     *
     * @OA\Response(
     *            response=500,
     *            description="Autre erreur"
     *       )
     *
     * @OA\Tag(name="Etablissements")
     * @Rest\View(serializerGroups={"detail"})
     * @ParamConverter("etablissement", converter="fos_rest.request_body")
     */
    public function update(Etablissement $etablissement, ParamFetcherInterface $paramFetcher, Request $request, ConstraintViolationListInterface $validationErrors): Etablissement
    {
        // Validation des données reçues
        if (count($validationErrors) > 0) {
            throw new ValidationException($validationErrors);
        }

        $existingEtablissement = $this->em->getRepository(Etablissement::class)->find($request->get('id'));

        if ($existingEtablissement === null) {
            throw new NotFoundHttpException('L\'etablissement n\'existe pas');
        } else {
            $etablissement->getNom() != null ? $existingEtablissement->setNom($etablissement->getNom()) : "";
            $etablissement->getTelephone() != null ? $existingEtablissement->setTelephone($etablissement->getTelephone()) : "";
            $etablissement->getAdresse() != null ? $existingEtablissement->setAdresse($etablissement->getAdresse()) : "";
            $etablissement->getCodepostal() != null ? $existingEtablissement->setCodepostal($etablissement->getCodepostal()) : "";
            $etablissement->getVille() != null ? $existingEtablissement->setVille($etablissement->getVille()) : "";
            $etablissement->getPays() != null ? $existingEtablissement->setPays($etablissement->getPays()) : "";
            $etablissement->getServices() != null ? $existingEtablissement->setServices($etablissement->getServices()) : "";
        }
        $this->em->merge($existingEtablissement);
        $this->em->flush();

        return $existingEtablissement;
    }

    /**
     * Supprime un établissement depuis les données reçues
     * @param Etablissement $etablissement
     * @return string $message de confirmation
     *
     * @Rest\Delete(
     *     path = "/api/etablissements/{id}",
     *     name = "etablissements_delete",
     *     requirements = {"id"="\d+"},
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *           response="200",
     *           description="Etablissement supprimé",
     *           content={
     *               @OA\MediaType(
     *                   mediaType="application/json",
     *                   @OA\Schema(
     *                       @OA\Property(
     *                           property="id",
     *                           type="integer",
     *                           description="Identifiant de l'établissement supprimé"
     *                       ),
     *                       @OA\Property(
     *                           property="message",
     *                           type="string",
     *                           description="Message de la réponse"
     *                       ),
     *                       example={
     *                           "id": 1,
     *                           "message": "Etablissement supprimé",
     *                       }
     *                   )
     *               )
     *           }
     *       )
     *
     * @OA\Response(
     *        response=401,
     *        description="Unauthenticated"
     *   )
     *
     * @OA\Response(
     *        response=404,
     *        description="Etablissement non trouvé"
     *   )
     *
     * @OA\Response(
     *            response=500,
     *            description="Autre erreur"
     *       )
     *
     * @OA\Tag(name="Etablissements")
     * @Rest\View(serializerGroups={"detail"})
     */
    public function delete(Etablissement $etablissement)
    {
        // creation de la reponse
        $id = $etablissement->getId();
        $message = array(
            'id' => $id,
            'message' => 'Etablissement supprimé',
        );

        // suppression du demande
        $this->em->remove($etablissement);
        $this->em->flush();

        return $message;
    }

}
