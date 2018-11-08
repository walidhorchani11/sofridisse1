<?php

namespace Sogedial\SiteBundle\Service;

use Doctrine\ORM\EntityManager;
use Sogedial\SiteBundle\Entity\Client;
use Symfony\Component\HttpFoundation\Session\Session;


class MultiSiteService
{
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var EntityManager
     */
    protected $em;


    private $societe;           // code société à 3 chiffres (en string), "222" pour Sofridis ou "110" pour Logigua
    private $region;            // code region (le premier chiffre du code société)

    private $masterEnterpriseTwig; //"Sofridis", "Logigua"...
    private $masterEnterprise;  // "sofridis", "logigua"... en minuscules

    private $catalog;           // tel qu'utilisé par ingestion (similaire au CSV)
    private $catalogEx;         // les informations étendues (le même nombre d'entrées, dans le même ordre)
//    private $otherKnownSocietes; // les sociétés dont le code est reconnu dans l'ingestion, mais qui n'ont pas été testées

    private $idxEx;             // index dans catalog/catalogEx pour la société courante, ou -1

    public function __construct(Session $session, EntityManager $entityManager)
    {
        $this->session = $session;
        $this->em = $entityManager;

        // Il est inutile d'utiliser la base SQL, car elle n'aura pas les infos étendues.
        //$this->masterEnterprise = mb_convert_case($masterEnterprise, MB_CASE_LOWER, "UTF-8");
        //Test de recuperation du masterEnterprise

        $repositoryEntreprise = $this->em->getRepository('SogedialSiteBundle:Entreprise');

        $societe = $this->session->get('entreprise_courante');

        if ($societe) {
            $entreprise = $repositoryEntreprise->findOneByCode($societe);
            $this->masterEnterprise = mb_convert_case($entreprise->getNomEnvironnement(), MB_CASE_LOWER, "UTF-8");
            $this->masterEnterpriseTwig = ucfirst(strtolower($entreprise->getNomEnvironnement()));
        }

        /*$this->otherKnownSocietes = array('210');
        $this->catalog = array();
        $this->catalogEx = array();*/

        // attention : CSV sont du ANSI, pas UTF-8 comme le présent fichier
        // remarque : sofrigu != societe frigorifique.... => besoin de catalogEx[][0]
        $this->catalog[] = array('LOG', '1', '10', 'LOGIGUA', 'RUE J.CUGNOT ET G.EIFFEL', 'ZI JARRY', '97122', 'BAIE-MAHAULT', '', 0, '', '', '', '', '', '', strtolower('Logigua'));
        $this->catalogEx[] = array('logigua',
            array(
                'date-panier',
                'livraison',
                'promotion',
                'tarifs-enseigne'
            ),
            array(
                "franco" => 0,
                "type-contrat" => 1,
                "temperature" => array("ambient")
            )
        );

        $this->catalog[] = array('CAD', '1', '20', 'CADI SURGELES', "ZAC HOUELBOURG III", 'VOIE VERTE', '97122', 'BAIE-MAHAULT', 'france', 1, '', '', '0596300030', '0596300794', '', '', strtolower('Cadi'));
        $this->catalogEx[] = array('cadi',
            array(
                'tarifs-tarification',
                'date-panier',
                'livraison',
                'promotion'
            ),
            array(
                "franco" => 0,
                "type-contrat" => 1,
                "temperature" => array("negativeCold")
            )
        );

        $this->catalog[] = array('SOF', '2', '22', 'SOFRIDIS', "ZI PLACE D'ARMES", '', '97232', 'LAMENTIN', 'france', 1, '', '', '0596300030', '0596300794', '', '', strtolower('Sofridis'));
        $this->catalogEx[] = array('sofridis',
            array(
                'franco',                   // aujourd'hui : la présence de "franco" dans la fenêtre de configuration de client ; demain, on pourrait ajouter "blocage sur franco" (une autre feature?)
                'type-contrat',              // la présence de feature == la valeur est choisie par l'admin (il faut afficher)
                'date-panier',
                'livraison',
                'promotion',
                'tarifs-enseigne'
            ),
            array("franco" => 600,
                "temperature" => array("ambient")
            )
        );

        $this->catalog[] = array("SEB", "1", "30", "S.E.B.", "ZONE HOUELBOURG III", "VOIE VERTE", "97122", "BAIE-MAHAULT", "france", 1, NULL, NULL, '20160426', '', '', '', strtolower('Sofriber'));
        $this->catalogEx[] = array('sofriber',
            array(
                'tarifs-tarification',
                'poid_variable',
                'date-panier',
                'livraison',
                'promotion'
            ),
            array(
                "franco" => 0,           // la valeur par défaut
                "type-contrat" => 1,
                "temperature" => array("positiveCold")
            )
        );

        $this->catalog[] = array('SFG', '3', '01', 'SOFRIGU', '4,rue Yves PREVOT', 'Baduel', '97332', 'Cayenne-cedex', utf8_decode('Guyane_Française'), 0, '', '', '', '', '', '', strtolower('Sofrigu'));
        $this->catalogEx[] = array('sofrigu',
            array(
                'ingestion-ajouter-assortiment-complet-si-pas-d-assortiments',       // 2 actions : créer un assortiment complet + associer cet assortiment avec les clients sans assortiment
                'tarifs-degressifs',            // 2 actions : permettre les produits sans tarifs lors de la séclection SQL + appliquer toute la logique de tarifs degressifs
                'vente-par-unite',
                'date-panier',
                'livraison',
                'promotion'
            ),
            array("franco" => 0,
                "type-contrat" => 1,
                "temperature" => array("ambient", "positiveCold", "negativeCold")
            )
        );

        $this->catalog[] = array('SGE', '4', '01', 'Sogedial Exploitation', '419,rue des chantiers BP 5073', '', '76071', 'Le Havre', 'france', 1, '', '', '0184177409', '', '', '', strtolower('Sogedial'));
        $this->catalogEx[] = array('sogedial',
            array(
                'franco',                   // aujourd'hui : la présence de "franco" dans la fenêtre de configuration de client ; demain, on pourrait ajouter "blocage sur franco" (une autre feature?)
                //'type-contrat',             // la présence de feature == la valeur est choisie par l'admin (il faut afficher),
                'exportBDC',
                'tarifs-marge',
                'poidsVolume',
                'notPrintEnStock'
            ),
            array("franco" => 0,
                "type-contrat" => 1,
                "temperature" => array("ambient", "positiveCold", "negativeCold")
            )
        );

        /*
        * TODO: remplir les infos
        */
        $this->catalog[] = array('MVI', '2', '40', 'M.V.I.', '', '', '', '', '', 0, '', '', '', '', '', '', strtolower('Mvi'));
        $this->catalogEx[] = array('mvi',
            array(
                'tarifs-tarification',
                'date-panier',
                'livraison',
                'promotion'
            ),
            array("franco" => 0,
                "type-contrat" => 1,
                "temperature" => array("positiveCold", "negativeCold")
            )
        );

        /**
         * TODO : remplir les infos
         */
        $this->catalog[] = array('SCA', '2', '10', 'SCAGEX', '', '', '', '', '', 0, '', '', '', '', '', '', strtolower('Scagex'));
        $this->catalogEx[] = array('scagex',
            array(
                'franco',
                'tarifs-tarification',
                'date-panier',
                'livraison',
                'promotion'
            ),
            array("franco" => 0,
                "type-contrat" => 1,
                "temperature" => array("ambient","positiveCold", "negativeCold" )
            )
        );

        $this->catalog[] = array('RHF', '2', '50', 'Sofrima', 'Zi la lezarde', 'Zi la lezarde', '97232', 'LAMENTIN', '', 0, '', '', '', '', '', '', strtolower('Sofrima'));
        $this->catalogEx[] = array('sofrima',
            array(
                'date-panier',
                'livraison',
                'poid_variable',
                'promotion',
                'tarifs-tarification'
            ),
            array(
                "franco" => 0,
                "type-contrat" => 1,
                "temperature" => array("ambient", "positiveCold", "negativeCold")
            )
        );

        $this->catalog[] = array('GUA', '1', '50', 'Prodom', 'Marina bas du fort', 'Gal marina', '97110', 'pointe a pitre', '', 0, '', '', '', '', '', '', strtolower('Prodom'));
        $this->catalogEx[] = array('prodom',
            array(
                'date-panier',
                'livraison',
                'poid_variable',
                'promotion',
                'tarifs-tarification'
            ),
            array(
                "franco" => 0,
                "type-contrat" => 1,
                "temperature" => array("ambient", "positiveCold", "negativeCold")
            )
        );


        $this->idxEx = -1;
        foreach ($this->catalogEx as $idx => $rowEx) {
            if ($rowEx[0] === $this->masterEnterprise) {
                $this->idxEx = $idx;
                break;
            }
        }

        $this->societe = (($this->idxEx === -1) ? "XXX" : (($this->catalog[$this->idxEx][1]) . ($this->catalog[$this->idxEx][2])));
        $this->region = substr($this->societe, 0, 1);
        if (!$societe || $this->societe == "XXX") {
            $this->societe = (($this->idxEx === -1) ? "222" : (($this->catalog[$this->idxEx][1]) . ($this->catalog[$this->idxEx][2])));
            $this->region = substr($this->societe, 0, 1);
            $entreprise = $repositoryEntreprise->findOneByCode(222);
            $this->masterEnterprise = (($this->idxEx === -1) ? 'sofridis' : mb_convert_case($this->catalog[$this->idxEx][16], MB_CASE_LOWER, "UTF-8"));
            $this->masterEnterpriseTwig = (($this->idxEx === -1) ? 'Sofridis' : $this->catalog[$this->idxEx][16]);
            foreach ($this->catalogEx as $idx => $rowEx) {
                if ($rowEx[0] === $this->masterEnterprise) {
                    $this->idxEx = $idx;
                    break;
                }
            }

        }


    }

    // "222", "110", ...
    public function getSociete()
    {
        return $this->societe;
    }

    public function getSocieteByTrigram($trigram)
    {
        foreach ($this->catalog as $societe) {
            if ($societe[0] === $trigram) {
                return ($societe[1]) . ($societe[2]);
            }
        }
        return 'XXX';
    }

    public function getTrigramBySociete($code)
    {
        foreach ($this->catalog as $societe) {
            if ($societe[1] . $societe[2] === $code) {
                return $societe[0];
            }
        }
        return 'XXX';
    }

    public function getTrigram()
    {
        if ($this->idxEx === -1) {
            return false;
        }
        return $this->catalog[$this->idxEx][0];
    }

    // "1", "2", "3"...
    public function getRegion()
    {
        return $this->region;
    }

    // retourne le nom tel que "Sofridis", "Logigua"... pour les twig
    public function getMasterEnterpriseTwig()
    {
        return $this->masterEnterpriseTwig;
    }

    // retourne le nom tel que "Sofridis", "Logigua"... pour les twig
    public function setMasterEnterpriseTwig($masterEnterprise)
    {
        $this->region = substr($masterEnterprise, 0, 1);
        $this->masterEnterpriseTwig = $masterEnterprise;
        $this->masterEnterprise = mb_convert_case($masterEnterprise, MB_CASE_LOWER, "UTF-8");
    }

    // retourne le nom tel que "sofridis", "logigua"... en minuscules
    public function getMasterEnterprise()
    {
        return $this->masterEnterprise;
    }

    public function getMasterEnterpriseMaj()
    {
        return mb_convert_case($this->masterEnterprise, MB_CASE_UPPER, "UTF-8");
    }

    public function getMasterEnterpriseTitre()
    {
        return mb_convert_case($this->masterEnterprise, MB_CASE_TITLE, "UTF-8");
    }

    // SOF -> 222 ; LOG -> 110 ...
    // Cette fonction est utilisée par l'ingestion afin d'assurer le filtrage par société lorsque le fichier d'origine ne contient que
    // le code de société en lettres (et pas le code numérique)
    public function getSocieteNumByAlpha($alpha)
    {
        foreach ($this->catalog as $row) {
            if ($row[0] === $alpha) {
                return $row[1] . $row[2];
            }
        }
        return "XXX";
    }

    // vérifie s'il s'agit d'un numéro de société à 3 chiffres valide
    public function isSocieteValid($societe3)
    {
        foreach ($this->catalog as $row) {
            if ($row[1] . $row[2] === $societe3)      // il ne faut aucun trim ici !!
            {
                return true;
            }
        }
        /*foreach ($this->otherKnownSocietes as $code) {
            if ($code === $societe3) {
                return true;
            }
        }*/

        return false;
    }

    public function getFallbackEnterpriseCsv()
    {
        return $this->catalog;
    }

    // attention : CSV sont du ANSI, pas UTF-8 comme le présent fichier
    public function getFallbackRegionCsv()
    {
        $data = array();
        $data[] = array('1', 'Guadeloupe', '');
        $data[] = array('2', 'Martinique', '');
        $data[] = array('3', utf8_decode('Guyane_Française'), '');
        $data[] = array('4', 'Sogedial', '');
        return $data;
    }

    public function getEmailFallbackBase()
    {
        return '@catalogue.' . ($this->masterEnterprise) . '.fr';
    }

    public function getFeatures()
    {
        return $this->catalogEx[$this->idxEx][1];
    }

    public function hasFeature($feature_name)
    {

        foreach ($this->catalogEx as $idx => $rowEx) {
            if ($rowEx[0] === $this->masterEnterprise) {
                $this->idxEx = $idx;
                break;
            }
        }

        if ($this->idxEx === -1) {
            return false;
        }
        foreach ($this->catalogEx[$this->idxEx][1] as $present_feature) {
            if ($feature_name === $present_feature) {
                return true;
            }
        }
        return false;
    }

    public function getValue($value_name)
    {
        foreach ($this->catalogEx as $idx => $rowEx) {
            if ($rowEx[0] === $this->masterEnterprise) {
                $this->idxEx = $idx;
                break;
            }
        }

        if ($this->idxEx === -1) {
            return false;
        }
        if (isset($this->catalogEx[$this->idxEx][2][$value_name])) {
            return $this->catalogEx[$this->idxEx][2][$value_name];
        }
        return false;
    }

    public function hasTemperature($temp_name)
    {

        foreach ($this->catalogEx as $idx => $rowEx) {
            if ($rowEx[0] === $this->masterEnterprise) {
                $this->idxEx = $idx;
                break;
            }
        }

        if ($this->idxEx === -1) {
            return false;
        }
        if (isset($this->catalogEx[$this->idxEx][2]["temperature"])) {
            return in_array($temp_name, $this->catalogEx[$this->idxEx][2]["temperature"]);
        }
        return false;

    }

    public function hasTemperatureWithCodeEntreprise($temp_name, $entreprise)
    {
        $tmpIndex = -1;
        foreach ($this->catalog as $idx => $row) {
            if ($row[1] . $row[2] === $entreprise) {
                $tmpIndex = $idx;
                break;
            }
        }

        if ($tmpIndex === -1) {
            return false;
        }
        if (isset($this->catalogEx[$tmpIndex][2]["temperature"])) {
            return in_array($temp_name, $this->catalogEx[$tmpIndex][2]["temperature"]);
        }
        return false;
    }

    public function initSessionUser($token, $societeCC)
    {
        $user = $token->getUser();
        $user_id = $user->getId();

        if ($user->getEtat() == 'client') {
            $user_meta = $this->em->getRepository('SogedialUserBundle:User')->findOneById($user_id);

            if ($societeCC !== false) {
                $user_meta->setEntrepriseCourante($societeCC);
                $this->em->persist($user_meta);
                $this->em->flush();
                $this->session->set('entreprise_courante', $societeCC);
            }
            $user_info = $this->em->getRepository('SogedialSiteBundle:Client')->findOneBy(array("meta" => $user_meta->getMeta()->getCode(), "entreprise" => $user_meta->getEntrepriseCourante()));


            if ($this->hasFeature('tarifs-tarification')) {
                $this->session->set('code_tarification', $user_info->getTarification()->getCode());

            } else {
                $this->session->set('code_tarification', null);
            }

            $this->session->set('code_client', $user_info->getCode());
            $this->session->set('code_enseigne', $user_info->getEnseigne()->getCode());
            if ($this->getRegion() !== '3') {
                $this->session->set('code_assortiment', $this->getAssortimentValeur($user_info));
            } else {
                $this->session->set('code_assortiment', "777");
            }
            $this->session->set('entreprise_courante', $user_meta->getEntrepriseCourante());

            $repositoryEntreprise = $this->em->getRepository('SogedialSiteBundle:Entreprise');
            $societe = $this->session->get('entreprise_courante');
            if ($societe) {
                $entreprise = $repositoryEntreprise->findOneByCode($societe);
                $this->masterEnterprise = mb_convert_case($entreprise->getNomEnvironnement(), MB_CASE_LOWER, "UTF-8");
                $this->masterEnterpriseTwig = strtolower($entreprise->getNomEnvironnement());
                $this->societe = $societe;
                $this->region = substr($this->societe, 0, 1);
            }
        }
    }

    public function initSessionUserAdmin($token)
    {
        $user = $token->getUser();
        $user_id = $user->getId();
        $user_meta = $this->em->getRepository('SogedialUserBundle:User')->findOneById($user_id);
        $this->session->set('entreprise_courante', $user_meta->getEntrepriseCourante());
        $repositoryEntreprise = $this->em->getRepository('SogedialSiteBundle:Entreprise');
        $societe = $this->session->get('entreprise_courante');
        if ($societe) {
            $entreprise = $repositoryEntreprise->findOneByCode($societe);
            $this->masterEnterprise = mb_convert_case($entreprise->getNomEnvironnement(), MB_CASE_LOWER, "UTF-8");
            $this->masterEnterpriseTwig = strtolower($entreprise->getNomEnvironnement());
            $this->societe = $societe;
            $this->region = substr($this->societe, 0, 1);
        }
    }   

    public function getAssortimentValeur(Client $client){
        $findBy = array("client" => $client->getCode(), "assortimentCourant" => true);

        $assortimentClient = $this->em->getRepository('SogedialSiteBundle:AssortimentClient')->findOneBy($findBy);
 
        if($assortimentClient === NULL){
            return $assortimentClient;
            //@todo: handle the null-object issue
        }

        return $assortimentClient->getValeur();
    }

    public function getBaseAssortimentValeur($client){
        $findBy = array("client" => $client->getCode(), "as400assortiment" => true);

        $assortimentClient = $this->em->getRepository('SogedialSiteBundle:AssortimentClient')->findOneBy($findBy);
 
        if($assortimentClient === NULL){
            return $assortimentClient;
            //@todo: handle the null-object issue
        }

        return $assortimentClient->getValeur();
    }
}