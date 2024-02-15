<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service permettant la gestion du service Arca
 * @author Alexis BOTT, Gaël GREBERT, Loïc POIRE, Pierre DELOBEL
 */
class ArcaService
{

    // variables
    private $serviceContainer;
    private $logger;
    private $host;
    private $port;
    private $worldSearchUid;
    private $worldSearchPwd;
    private $locale;
    private $criteria;
    private $rno;
    private $ldap;

    /**
     * constructeur pour le service Arca
     * ouvre une connexion ldap
     */
    public function __construct(
        LoggerInterface $logger,
        ContainerInterface $container
    ) {
        $this->serviceContainer = $container;
        $this->logger = $logger;

        $this->host = $this->serviceContainer->getParameter('arcaHost');
        $this->port = $this->serviceContainer->getParameter('arcaPort');
        $this->locale = $this->serviceContainer->getParameter('arcaLocale');
        $this->criteria = $this->serviceContainer->getParameter('arcaCriteria');
        $this->rno = $this->serviceContainer->getParameter('arcaRno');
        $this->worldSearchPwd = $this->serviceContainer->getParameter('arcaWorldSearchPwd');
        $this->worldSearchUid = $this->serviceContainer->getParameter('arcaWorldSearchUid');

        $this->ldap = ldap_connect($this->host, $this->port);
    }

    /**
     * fermer la connexion ldap
     */
    public function __destruct()
    {
        ldap_close($this->ldap);
    }

    /**
     * S'authentifie auprés d'Arca
     * @param string ipn, ipn de l'utilisateur
     * @param string pw, mot de passe de l'utilisateur
     */
    public function authenticate(string $ipn, string $pw)
    {
        $ret = false;
        try {
            // Formatage de l'ipn
            if (substr($ipn, 0, 1) == 1) {
                $uid = 'A' . substr($ipn, 1);
            }

            // Recherche de la locale ('dn') si aucune de parametrée
            $base = '';
            if (!$this->locale || 'xxx' == strtolower($this->locale)) {
                // connexion à Arca via l'ipn virtuel pour faire une recherche
                $personInfo = $this->getPersonInfo($ipn);
                $base = $personInfo['dn'];
            } else {
                $base = 'uid=' . $ipn . ',ou=' . $this->locale . ',ou=' . $this->criteria . ',o=' . $this->rno;
            }

            $ret = ldap_bind($this->ldap, $base, $pw);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $ret;
    }

    /**
     * Récupère le retour de la requete de recherche ldap
     * @param string search, valeur utilisée pour la recherche
     * @param bool byName, si false alors search est l'ipn de la personne. Si true, c'est le nom et/ou le prénom (le nom toujours en premier)
     */
    public function searchEntries(string $search, bool $byName = false)
    {
        // Vérification de la locale
        if (!$this->locale || 'xxx' == strtolower($this->locale)) {
            $base = 'uid=' . $this->worldSearchUid . ',ou=xxx,ou=' . $this->criteria . ',o=' . $this->rno;

            // Connexion via l'ipn virtuel au arca monde
            ldap_bind($this->ldap, $base, $this->worldSearchPwd);
        }

        if ($byName) {
            $search = '(cn=' . $search . '*)';
        } else {
            $search = '(uid=' . $search . ')';
        }

        $searchRequest = ldap_search($this->ldap, 'o=renault', $search);

        if (ldap_count_entries($this->ldap, $searchRequest) > 0) {
            $results = ldap_get_entries($this->ldap, $searchRequest);

            // Conversion en utf8 des résultats
            if (isset($results[0]['jpegphoto'])) {
                $results[0]['jpegphoto'][0] = base64_encode($results[0]['jpegphoto'][0]);
            }
            $results = $this->utf8_converter($results);

            return $results;
        } else {
            throw new \Exception("Aucun résultat pour " . $search);
        }
    }

    /**
     * Formate les infos de la personne reçues depuis ldap
     */
    public function getPersonInfo(string $ipn)
    {
        $infos = [];
        try {
            $formatedInfos = null;
            $infos = $this->searchEntries($ipn);

            if ($infos && isset($infos[0])) {
                $firstName = $infos[0]["givenname"][0];
                $lastName = $infos[0]["sn"][0];
                $dn = $infos[0]["dn"];
                $mail = '';
                if (array_key_exists('mail', $infos[0])) {
                    $mail = $infos[0]["mail"][0];
                }
                $codeSite = $infos[0]["postofficebox"][0];

                $formatedInfos = [];
                $formatedInfos['firstName'] = $firstName;
                $formatedInfos['lastName'] = $lastName;
                $formatedInfos['mail'] = $mail;
                $formatedInfos['codeSite'] = $codeSite;
                $formatedInfos['dn'] = $dn;
            } else {
                throw new \Exception("Erreur Arca. Vérifier que la valeur recherchée (Ex: Ipn) est valide et existe.");
            }
        } catch (\Exception $e) {
            throw new \Exception("ArcaService => getPersonInfo : " . $e->getMessage() . ". Données reçues: " . json_encode($infos));
        }

        return $formatedInfos;
    }

    /**
     * Convertisseur utf8 récursif
     * @param array, tableau de string (pas d'objets)
     */
    function utf8_converter($array)
    {
        array_walk_recursive($array, function (&$item, $key) {
            if (!mb_detect_encoding($item, 'utf-8', true)) {
                $item = mb_convert_encoding($item, 'UTF-8');
            }
        });
        return $array;
    }
}
