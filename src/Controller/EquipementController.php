<?php

namespace App\Controller;

use App\Entity\Equipement;
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


class EquipementController extends AbstractFOSRestController
{
    // variables
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Renvoie une liste d'équipements
     * @return Equipement[]
     *
     * @Rest\Get(
     *     path = "/api/equipements",
     *     name = "equipements_list",
     *     options = {"tokenSecurity"}
     * )
     * @OA\Response(
     *       response=200,
     *       description="Retourne les informations d'un ou plusieurs équipements",
     *       @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=Equipement::class, groups={"list"}))
     *       )
     *   )
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
     * @OA\Tag(name="Equipements")
     *
     * @Rest\QueryParam(name="nom")
     * @Rest\QueryParam(name="profil")
     * @Rest\QueryParam(name="id")
     * @Rest\View(serializerGroups={"list"})
     */
    public function getWithFilter(ParamFetcherInterface $paramFetcher, Request $request)
    {
        // paramètres du paginator
        $classe = 'Equipement';
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

        $entities = $this->em->getRepository(Equipement::class)->findWithFiltersAndPaginator($page, $limit, $orderBy, $filter_text, $params, $classe, $exceptedParams);
        return $entities;
    }

    /**
     * Crée un équipement depuis les données reçues
     * @param Equipement $equipement
     * @return Equipement
     *
     * @Rest\Post(
     *     path = "/api/equipements",
     *     name = "equipements_create",
     *     options = {"tokenSecurity"}
     * )
     * @OA\Response(
     *           response=201,
     *           description="Retourne les informations de l'équipement créé",
     *           @OA\JsonContent(
     *              type="array",
     *              @OA\Items(ref=@Model(type=Equipement::class, groups={"list"}))
     *           )
     *       )
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
     * @OA\Tag(name="Equipements")
     *
     * @Rest\View(statusCode = 201, serializerGroups={"detail"})
     * @ParamConverter("equipement", converter="fos_rest.request_body")
     */
    public function create(Equipement $equipement, ConstraintViolationListInterface $validationErrors)
    {
        // Validation des données reçues
        if (count($validationErrors) > 0) {
            throw new ValidationException($validationErrors);
        }
        $this->em->persist($equipement);
        $this->em->flush();

        return $equipement;
    }

    /**
     * Met à jour un équipement depuis les données reçues
     * @param Equipement $equipement
     * @param ConstraintViolationListInterface $validationErrors
     * @return Equipement
     *
     * @throws ValidationException
     * @Rest\Put(
     *     path = "/api/equipements/{id}",
     *     name = "equipements_update",
     *     requirements = {"id"="\d+"},
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *        response=200,
     *        description="Retourne les informations de l'équipement modifié",
     *        @OA\JsonContent(
     *           type="array",
     *           @OA\Items(ref=@Model(type=Equipement::class, groups={"list"}))
     *        )
     *    )
     *
     * @OA\Response(
     *      response=401,
     *      description="Unauthenticated"
     * )
     *
     * @OA\Response(
     *      response=404,
     *      description="Equipement non trouvé"
     * )
     *
     * @OA\Response(
     *          response=500,
     *          description="Autre erreur"
     *     )
     *
     * @OA\Tag(name="Equipements")
     *
     * @Rest\View(serializerGroups={"detail"})
     * @ParamConverter("equipement", converter="fos_rest.request_body")
     **/
    public function update(Equipement $equipement, ParamFetcherInterface $paramFetcher, Request $request, ConstraintViolationListInterface $validationErrors): Equipement
    {
        // Validation des données reçues
        if (count($validationErrors) > 0) {
            throw new ValidationException($validationErrors);
        }

        $existingEquipement = $this->em->getRepository(Equipement::class)->find($request->get('id'));

        if ($existingEquipement === null) {
            throw new NotFoundHttpException('L\'equipement n\'existe pas');
        } else {
            $equipement->getNom() != null ? $existingEquipement->setNom($equipement->getNom()) : "";
            $equipement->getDescription() != null ? $existingEquipement->setDescription($equipement->getDescription()) : "";
            $equipement->getMarque() != null ? $existingEquipement->setMarque($equipement->getMarque()) : "";
            $equipement->getModele() != null ? $existingEquipement->setModele($equipement->getModele()) : "";
            $equipement->getTypeEquipement() != null ? $existingEquipement->setTypeEquipement($equipement->getTypeEquipement()) : "";
            $equipement->getVoiture() != null ? $existingEquipement->setVoiture($equipement->getVoiture()) : "";
        }
        $this->em->merge($existingEquipement);
        $this->em->flush();

        return $existingEquipement;
    }

    /**
     * Supprime un équipement depuis les données reçues
     * @param Equipement $equipement
     * @return string $message de confirmation
     *
     * @Rest\Delete(
     *     path = "/api/equipements/{id}",
     *     name = "equipements_delete",
     *     requirements = {"id"="\d+"},
     *     options = {"tokenSecurity"}
     * )
     *
     * @OA\Response(
     *          response="200",
     *          description="Equipement supprimé",
     *          content={
     *              @OA\MediaType(
     *                  mediaType="application/json",
     *                  @OA\Schema(
     *                      @OA\Property(
     *                          property="id",
     *                          type="integer",
     *                          description="Identifiant de l'équipement supprimé"
     *                      ),
     *                      @OA\Property(
     *                          property="message",
     *                          type="string",
     *                          description="Message de la réponse"
     *                      ),
     *                      example={
     *                          "id": 1,
     *                          "message": "Equipement supprimé",
     *                      }
     *                  )
     *              )
     *          }
     *      )
     *
     * @OA\Response(
     *       response=401,
     *       description="Unauthenticated"
     *  )
     *
     * @OA\Response(
     *       response=404,
     *       description="Equipement non trouvé"
     *  )
     *
     * @OA\Response(
     *           response=500,
     *           description="Autre erreur"
     *      )
     *
     * @OA\Tag(name="Equipements")
     *
     * @Rest\View(serializerGroups={"detail"})
     */
    public function delete(Equipement $equipement)
    {
        // creation de la reponse
        $id = $equipement->getId();
        $message = array(
            'id' => $id,
            'message' => 'Equipement supprimé',
        );

        // suppression du demande
        $this->em->remove($equipement);
        $this->em->flush();

        return $message;
    }

}
