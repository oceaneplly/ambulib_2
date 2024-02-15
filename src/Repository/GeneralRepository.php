<?php

namespace App\Repository;

use App\Exception\EntityNotFoundException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;



/**
 * GeneralRepository
 *
 */
class GeneralRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry, $entityClass)
    {
        parent::__construct($registry, $entityClass);
    }

    /**
     * Renvoie les entités en fonction de critères définis dans le where
     *
     * @param array $where
     */
    public function getCount($where = [], $orWheres = null, $joins = null)
    {
        $qb = $this->createQueryBuilder("a")
            ->select('count(a)');

        // -- WHERE
        if (null !== $where and count($where) !== 0) {
            $where_string = implode(' AND ', $where);
            $qb->where($where_string);
            // die(var_dump($where_string));
        }

        // -- OR WHERE (pour jointure)
        if (null !== $orWheres && count($orWheres) > 0) {
            $orWhere_string = implode(' OR ', $orWheres);
            $qb->andWhere($orWhere_string);
        }

        // -- JOINTURE
        if (null !== $joins && count($joins) > 0) {
            foreach ($joins as $join) {
                $join = explode(';', $join);
                $qb->innerJoin($join[0], $join[1], "WITH", $join[2]);
            }
        }

        // GET RESULTS
        $result = $qb->getQuery()->getSingleScalarResult();
        return $result;
    }

    /**
     * Renvoie les entités en fonction de critères définis dans le where
     *
     * @param array $where
     * @param array/string $orderBy
     * @param integer $limit
     * @param integer $offset
     * @return array $entities
     */
    public function findByMore($wheres = [], $orderBy = null, $limit = null, $offset = null, $orWheres = [], $joins = [])
    {
        $qb = $this->createQueryBuilder("a");
        // -- AND WHERE
        if (null !== $wheres and count($wheres) !== 0) {
            $where_string = implode(' AND ', $wheres);
            $qb->where($where_string);
        }

        // -- OR WHERE (pour jointure)
        if (null !== $orWheres && count($orWheres) > 0) {
            $orWhere_string = implode(' OR ', $orWheres);
            $qb->andWhere($orWhere_string);
        }

        // -- JOINTURE
        if (null !== $joins && count($joins) > 0) {
            foreach ($joins as $join) {
                $join = explode(';', $join);
                $qb->leftJoin($join[0], $join[1], "WITH", $join[2]);
            }
        }

        // -- LIMIT
        if (null !== $limit && intval($limit) > 0) {
            $qb->setMaxResults($limit);
        }
        // -- OFFSET
        if (null !== $offset && intval($offset) > 0) {
            $qb->setFirstResult($offset);
        }
        // -- ORDER BY
        if (null !== $orderBy) {
            $orderByElts = explode(',', $orderBy);
            foreach ($orderByElts as $key => $orderByElt) {
                $orderByElt = explode(' ', $orderByElt);
                if (count($orderByElt) != 2 || !in_array(strtolower($orderByElt[1]), ['desc', 'asc'])) {
                    throw new \Exception("Parameter orderBy invalide. Exemple valide: 'orderBy=id ASC, name DESC'");
                }

                $orderBySubElts = explode('.', $orderByElt[0]);
                if (count($orderBySubElts) == 2) { // cas d'orderBy sur une propriété d'un enfant
                    // Ajout de la jointure
                    $join = 'a.' . $orderBySubElts[0];
                    $alias = substr($orderBySubElts[0], 0, 2);
                    $qb->leftJoin($join, $alias, "WITH", $join . ' = ' . $alias . '.id');

                    // Ajout du orderBy
                    $qb->addOrderBy($alias . '.' . $orderBySubElts[1], $orderByElt[1]);
                } else if (count($orderBySubElts) == 1) {  // cas d'un orderBy classique
                    $qb->addOrderBy('a.' . $orderByElt[0], $orderByElt[1]);
                } else {
                    throw new \Exception("Parameter orderBy invalide. Exemple valide: 'orderBy=id ASC, name DESC'");
                }
            }
        }
        // GET RESULTS
        // die($qb->getDQL());
        return $qb->getQuery()->getResult();
    }

    /**
     * Renvoie les entités en fonction de critères donnés avec prise en compte du paginator
     *
     * @param integer $page
     * @param integer $limit
     * @param array/string $orderBy
     * @param string $filterText
     * @param array $params (paramètres de l'entité à filtrer, à inclure)
     * @param string $classe (classe de l'entité)
     * @param array $exceptedParams (paramètres de l'entité à filtrer, à exclure)
     * @param array $customWheres (conditions "custom" à ajouter)
     * @param array $customJoins (jointures "custom" à ajouter)
     * @return array $entities
     */
    public function findWithFiltersAndPaginator(int $page, int $limit, $orderBy, string $filterText, $params, string $classe, $exceptedParams = [], $customWheres = [], $customJoins = [])
    {

        // paramètres
        $classObjet = ClassUtils::newReflectionClass('App\Entity\\' . $classe);
        $reader = new AnnotationReader();

        $wheres = [];
        $orWheres = [];
        $joins = [];
        $isPaginated = true;
        if (!$page && !$limit) {
            $isPaginated = false;
        }

        // prise en compte des paramètres de filtres standards à inclure
        foreach ($params as $key => $value) {
            if ($value || '0' === $value) {
                if (strpos($key, '_id') !== false) {
                    $key = str_replace('_id', '', $key);
                }
                $where = '';
                if (count(explode(',', $value)) > 1) {
                    $value = "('" . implode("','", explode(',', $value)) . "')";
                    $where = "a." . $key . " IN " . $value;
                } else {
                    if ($value == 'true' || $value == 'false') {
                        $where = "a." . $key . " = " . $value;
                    } else {
                        $where = "a." . $key . " = '" . $value . "'";
                    }
                }
                $wheres[] = $where;
            }
        }
        // die(var_dump($wheres));

        // prise en compte des paramètres de filtres standards à exclure
        foreach ($exceptedParams as $key => $value) {

            // formatage des clés étrangères
            if (strpos($key, '_id') !== false) {
                $key = str_replace('_id', '', $key);
            }

            // formatage de la réponse
            if ($classObjet->hasProperty($key)) {
                $property = $classObjet->getProperty($key);
                $annotation = $reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\Column');
                $valueType = gettype($value);

                // Cas d'une propriété non relationnelle de l'objet (ex: string, boolean,...)
                if ($annotation && property_exists($annotation, 'type')) {
                    $propertyType = $annotation->type;
                    // vérification du type de paramètre fourni
                    if ($valueType != 'NULL' && $valueType != 'array' && $valueType != $propertyType) {
                        throw new \UnexpectedValueException('Paramètre except invalide => "' . $key . '" a le type "' . $propertyType . '" mais type "' . $valueType . '" fourni.');
                    }
                } else {
                    // Cas d'une propriété relationnelle de l'objet (clé étrangère) -> ManyToOne
                    $propertyComments = $property->getDocComment();
                    if (strpos($propertyComments, 'targetEntity') !== false) {
                        // Vérification si ManyToMany ou OneToMany
                        if (strpos($propertyComments, 'ManyToMany') !== false || strpos($propertyComments, 'OneToMany') !== false) {
                            throw new \UnexpectedValueException('Paramètre except invalide => clé "' . $key . '" non acceptée.');
                        }

                        // vérification du type de paramètre fourni
                        if ($valueType === 'array') {
                            if (count($value) === 0) {
                                throw new \UnexpectedValueException('Paramètre except invalide => "' . $key . '" n\'accepte que des entiers ou des tableaux d\'entiers. Tableau vide fourni.');
                            }

                            $propertyType = 'integer';
                        } else if ($valueType !== 'integer') {
                            throw new \UnexpectedValueException('Paramètre except invalide => "' . $key . '" n\'accepte que des entiers ou des tableaux d\'entiers');
                        }
                    }
                }

                $where = '';
                switch ($valueType) {
                    case 'NULL':
                        $where = "a." . $key . " IS NOT NULL";
                        break;

                    case 'boolean':

                        if ($value) {
                            $where = "a." . $key . " != true";
                        } else {
                            $where = "a." . $key . " != false";
                        }
                        break;

                    case 'string':
                        $where = "a." . $key . " != '" . $value . "'";
                        break;

                    case 'array':
                        if (count($value) > 0) {
                            // vérification du type des paramètres fournis dans le tableau
                            foreach ($value as $subValue) {
                                $subValueType = gettype($subValue);
                                if ($subValueType != $propertyType) {
                                    throw new \UnexpectedValueException('Paramètre except invalide => "' . $key . '" possède des valeurs incorrects: "' . $propertyType . '" attendu mais type "' . $subValueType . '" fourni.');
                                }
                            }

                            $value = "('" . implode("','", $value) . "')";
                            $where = "a." . $key . " NOT IN " . $value;
                        } else {
                            throw new \UnexpectedValueException('Paramètre except invalide => le tableau pour "' . $key . '" est vide');
                        }
                        break;

                    case 'object':
                        // on ne fait rien
                        break;
                    default: // integer - double
                        $where = "a." . $key . " != " . $value;
                        break;
                }

                // Ajout à la liste des critères
                if ($where) {
                    $wheres[] = $where;
                }
            } else {
                throw new \UnexpectedValueException('Le paramètre ' . $key . ' n\'existe pas.');
            }
        }

        // die(var_dump($wheres));

        // prise en compte du filtre global
        $filterCriteria = $this->generateFilterCriteria($classe, $filterText, true);

        if (count($filterCriteria['where']) > 0) {
            $orWheres = $filterCriteria['where'];
        }

        if (count($filterCriteria['join']) > 0) {
            $joins = $filterCriteria['join'];
        }

        // Ajout des customWheres et customJoins
        $wheres = array_merge($wheres, $customWheres);
        $joins = array_merge($joins, $customJoins);

        // recherche des resultats
        $offset = null;
        if ($isPaginated) {
            $offset = ($page - 1) * $limit;
        }

        $entities = $this->findByMore($wheres, $orderBy, $limit, $offset, $orWheres, $joins);

        // calcul du nombre de page (paginator)
        $nombre_page = 1;
        $total = count($entities);
        if ($isPaginated) {
            $total = $this->getCount($wheres, $orWheres, $joins);
            $nombre_page = ceil($total / $limit);
        }

        // construction de la réponse
        if ($entities) {
            $response = [];
            $response['results'] = $entities;
            if ($isPaginated) {
                $response['paginator'] = [
                    "rows_per_page" => $limit,
                    "page_count" => $nombre_page,
                    "total" => $total,
                ];
            }
        } else {
            return [];
        }
        return $response;
    }

    /**
     * Renvoie le dernier element de la table
     * @param string $classe (classe de l'entité)
     * @return Object $element
     */
    public function getLastElement()
    {
        $lastElement = $this->createQueryBuilder('a')
            ->select('a')
            ->orderBy('a.id','DESC')
            ->getQuery()
            ->setMaxResults(1)
            ->getResult();
        return $lastElement;
    }

    public function findOneByDemande($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.iddemande = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getResult();
        ;
    }

    /**
     * Renvoie l'id du dernier element de la table
     *
     * @return integer $id
     */
    public function getLastId()
    {
        $qb = $this->createQueryBuilder("a")
            ->select('max(a.id)');

        return intval($qb->getQuery()->getSingleScalarResult());
    }


    /**
     * Génère les filterCriteria (élement de requête doctrine) pour la recherche sur le filtre global
     * @param String $classe, classe de l'entité à scanner
     * @param String $filterText, valeur recherchée
     * @param String $params, paramètres de l'entité autorisé pour la recherche
     * @return array<FilterCriterion>
     */
    public function generateFilterCriteria(string $classe, string $filterText, bool $childScan = false, string $alias = 'a')
    {
        // paramètres
        $classObjet = ClassUtils::newReflectionClass('App\Entity\\' . $classe);
        $classProperties = $classObjet->getProperties();
        $reader = new AnnotationReader();
        static $filterCriteria = [
            'where' => [],
            'join' => [],
        ];

        // Scan de l'entité et génération des requêtes Doctrine
        if ($filterText != '') {
            foreach ($classProperties as $property) {
                $propertyComments = $property->getDocComment();
                // Si la propriété n'est pas une relation avec une autre entité
                if (
                    strpos($propertyComments, 'ManyToMany') === false
                    && strpos($propertyComments, 'OneToMany') === false
                    && strpos($propertyComments, 'ManyToOne') === false
                    && strpos($propertyComments, 'OneToOne') === false
                ) {

                    $propertyType = $reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\Column')->type;

                    switch ($propertyType) {
                        case 'integer':
                            if (is_numeric($filterText)) {
                                $filterCriteria['where'][] = $alias . "." . $property->getName() . " = " . intval($filterText);
                            }
                            break;

                        case 'float':
                            if (is_numeric($filterText)) {
                                $filterCriteria['where'][] = $alias . "." . $property->getName() . " = " . floatval($filterText);
                            }
                            break;

                        case 'datetime':
                            // verification si date
                            if ($this->validateDate($filterText)) {
                                // Verification si date française
                                if (\preg_match('/^([0-2][0-9]|(3)[0-1])(\/)(((0)[0-9])|((1)[0-2]))(\/)\d{4}$/', $filterText) === 1) {
                                    $filterText = explode('/', $filterText);
                                    $filterText = implode('/', array_reverse($filterText));
                                }

                                $startDate = new \DateTime($filterText);
                                $endDate = clone ($startDate);
                                $endDate->add(new \DateInterval('P1D'));

                                // die(var_dump($startDate));
                                $filterCriteria['where'][] = $alias . "." . $property->getName() . " BETWEEN '" . $startDate->format('Y-m-d H:i') . "' AND '" . $endDate->format('Y-m-d H:i') . "'";
                            }
                            break;

                        case 'time':
                            // verification si time
                            if ($this->validateTime($filterText)) {
                                $startDate = new \DateTime($filterText);
                                $endDate = clone ($startDate);
                                $endDate->add(new \DateInterval('PT1M'));

                                $filterCriteria['where'][] = $alias . "." . $property->getName() . " BETWEEN '" . $startDate->format('H:i') . "' AND '" . $endDate->format('H:i') . "'";
                            }
                            break;

                        case 'json_array':
                            // à développer
                            break;

                        case 'boolean':
                            if (in_array(strtolower($filterText), ['true', 'vrai', 'oui'])) {
                                $filterCriteria['where'][] = $alias . "." . $property->getName() . " = true";
                            } else if (in_array(strtolower($filterText), ['false', 'faux', 'non'])) {
                                $filterCriteria['where'][] = $alias . "." . $property->getName() . " = false";
                            }
                            break;

                        default: // string
                            $filterCriteria['where'][] = "LOWER(" . $alias . "." . $property->getName() . ") LIKE '%" . mb_strtolower($filterText) . "%'";
                            break;
                    }
                } else if ($childScan) {    // S'il s'agit d'une relation vers une autre entité
                    //Cas d'une ManyToOne
                    if (strpos($propertyComments, 'ManyToOne')) {
                        // Préparation des jointures
                        $propertyAnnotation = $reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\ManyToOne');
                        $childAlias = substr($property->getName(), 0, 2);
                        $filterCriteria['join'][] = $propertyAnnotation->targetEntity . ';' . $childAlias . ';' . $alias . '.' . $property->getName() . ' = ' . $childAlias . '.id'; // [classe, alias, jointure]

                        // préparation des orWhere
                        $childClass = explode('\\', $propertyAnnotation->targetEntity)[2];

                        $this->generateFilterCriteria($childClass, $filterText, false, $childAlias);
                    }
                }
            }
        }
        return $filterCriteria;
    }

    // Vérifie si la string fournie est une date valide
    public function validateDate(string $date)
    {
        $result = false;
        $formats = ['Y-m-d', 'Y/m/d', 'd-m-Y', 'd/m/Y'];

        foreach ($formats as $format) {
            $newDate = \DateTime::createFromFormat($format, $date);
            if ($newDate && $newDate->format($format) == $date) {
                $result = true;
            }
        }

        return $result;
    }

    // Vérifie si la string fournie est une date valide
    public function validateTime(string $time)
    {
        $result = false;
        $formats = ['H:i', 'H:i:s', 'G:i:s', 'G:i'];

        foreach ($formats as $format) {
            $newDate = \DateTime::createFromFormat($format, $time);
            if ($newDate && $newDate->format($format) == $time) {
                $result = true;
            }
        }

        return $result;
    }
}
