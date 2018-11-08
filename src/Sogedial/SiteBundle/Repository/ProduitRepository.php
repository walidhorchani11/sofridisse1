<?php

namespace Sogedial\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Sogedial\ComponentBundle\Lib\Exception\NotFoundException;
use Sogedial\SiteBundle\Entity\Client;
use Sogedial\SiteBundle\Entity\Photo;
use Sogedial\SiteBundle\Entity\Produit;
use Symfony\Component\Config\Definition\Exception\Exception;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\Validator\Constraints\DateTime;
use Sogedial\SiteBundle\Service\UseIndexWalker;

/**
 * ProduitRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ProduitRepository extends EntityRepository
{

    /**
     * @param $codeProduit
     * @return array
     */
    public function getProduitByCode($codeProduit)
    {
        $qb = $this->createQueryBuilder('c');

        $qb->add('select', 'c')
            ->add('from', 'SogedialSiteBundle:Produit c')
            ->where('c.code = :produitCode');

        $qb->setParameter('produitCode', $codeProduit);

        return $qb->getQuery()->getSingleResult();
    }

    public function resetInFlux($region)
    {
        $r = intval($region);
        //security
        if ($r < 1) {
            return false;
        }
        $qb = $this->_em->createQueryBuilder();
        $q = $qb->update('SogedialSiteBundle:Produit', 'p')
            ->set("p.inAchatLogidis", 0)
            ->where("p.code LIKE :region")
            ->setParameter('region', $region . "%")
            ->getQuery();
        return $q->execute();
    }

    public function disableProdutsByRegion($region)
    {
        $r = intval($region);
        //security
        if ($r < 1) {
            return false;
        }

        $now = "";
        if ($region === 4) {
            $now = "'" . (new \DateTime('now'))->format('Y-m-d H:i:s.000') . "'";
        } else {
            $now = "'" . (new \DateTime('now'))->format('Y-m-1 0:0:0.000') . "'";
        }

        $qb = $this->_em->createQueryBuilder();
        $q = $qb->update('SogedialSiteBundle:Produit', 'p')
            ->set("p.actif", 0)
            ->set("p.deletedAt", $now)
            ->where("p.code LIKE :region")
            ->andWhere("p.inAchatLogidis = 0");
        /*if ($r === 4) {
            $qb->andWhere("p.deletedAt < :twomonths");
        }*/
        $qb->setParameter('region', $region . "%");
        /*if ($r === 4) {
            $qb->setParameter('twomonths', new \DateTime('-2 months'));
        }*/
        return $q->getQuery()->execute();
    }

    public function getRecapByOrder($orderId, $multiplyByPcb = true, $stockColis = true, $codeClient = "")
    {
        //Quick fix probleme code client original precommande
        $codeClientOrigine = $codeClient;
        if ($codeClient != "") {
            $qbEntreprise = $this->_em->createQueryBuilder();
            $codeEntrepriseClient = explode("-", $codeClient)[0];

            $qbEntreprise->select('e')
                ->from('SogedialSiteBundle:Entreprise', 'e')
                ->where("e.code = :entreprise")
                ->setParameter('entreprise', $codeEntrepriseClient);

            $entreprise = current($qbEntreprise->getQuery()->getResult());
            if ($entreprise->getEntrepriseParent() != null) {
                $codeClientOrigine = $entreprise->getEntrepriseParent()->getCode() . "-" . explode("-", $codeClient)[1];
            }
        }

        $qb = $this->_em->createQueryBuilder();

        $qb
            ->select('DISTINCT p.code, p.poidsVariable as poid_variable, p.saleUnity as sale_unity, p.denominationProduitBase, p.pcb, p.ean13, p.temperature, p.marketingCode, p.natureCode, p.dureeVieJours as dureeDeVie, p.dlc as dlcProduit')
            ->addSelect('ent.raisonSociale as entreprise')
            ->addSelect($stockColis ? ' st.stockTheoriqueColis as stock' : ' st.stockTheoriqueUc as stock')
            ->addSelect('f3.code as sf, f3.libelle as sf_fr')
            ->addSelect('m.libelle as marque')
            ->addSelect('op.quantite')
            ->addSelect(' ROUND(op.prixUnitaire, 2) as prixHt, ROUND(op.prixUnitaire * p.pcb, 2) as colisPrice, ' .
                ($multiplyByPcb ? ' ROUND(op.prixUnitaire * op.quantite * p.pcb, 2) as totalPrice' : ' ROUND(op.prixUnitaire * op.quantite, 2) as totalPrice')
            )
            ->addSelect('pm.produitMeti')
            ->addSelect('cls.volumeColis, cls.poidsBrutColis as poidsColis')
            ->addSelect('cpm.quantiteMinimale as moq_client')
            ->from('SogedialSiteBundle:Commande', 'o');
        $qb
            ->leftJoin('SogedialSiteBundle:LigneCommande', 'op',
                'WITH', 'op.commande = o')
            ->leftJoin('SogedialSiteBundle:Entreprise', 'ent',
                'WITH', 'ent = o.entreprise')
            ->leftJoin('op.produit', 'p')
            ->leftJoin('SogedialSiteBundle:Client', 'c',
                'WITH', "c.code = '" . $codeClient . "'")
            ->leftJoin('SogedialSiteBundle:ClientProduitMOQ', 'cpm',
                'WITH', 'cpm.client = c AND cpm.produit = p')
            ->leftJoin('SogedialSiteBundle:ProduitMeti', 'pm',
                'WITH', "pm.produit = p AND pm.client = '" . $codeClientOrigine . "'")
            ->leftJoin('SogedialSiteBundle:Colis', 'cls',
                'WITH', 'cls.produit = p')
            ->leftJoin('p.marque', 'm')
            ->leftJoin('p.famille', 'f3')
            ->leftJoin('p.rayon', 'f2')
            ->leftJoin('p.stock', 'st')
            ->where('o.id = :orderId')
            ->andWhere('p.code = st.produit')
            ->andWhere('p.actif=1')
            ->setParameter('orderId', $orderId)
            ->addOrderBy('f3.libelle', 'ASC')
            ->addOrderBy('p.denominationProduitBase', 'ASC')
        ;

        $query = $qb->getQuery();
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, '\Sogedial\SiteBundle\Service\UseIndexWalker');
        $query->setHint(UseIndexWalker::HINT_USE_INDEX, 'commande_idx');

        return $query->getScalarResult();
    }

    /**
     * @param $orderId
     * @param $temperature
     * @param bool $multiplyByPcb
     * @param bool $stockColis
     * @param bool $panier
     * @return array
     */
    public function getRecapByOrderForOrderDetails($orderId, $temperature, $multiplyByPcb, $stockColis, $panier = false)
    {
        $result = [];
        $codeClientOrigine = $this->_em->getRepository('SogedialSiteBundle:Commande')->getCodeClientOriginal($orderId);
        $checkStock = ($multiplyByPcb) ? "s.stockTheoriqueColis != 0" : "s.stockTheoriqueUc != 0";
        $params = array('orderId' => $orderId);
        if (!$panier) {
            $params['temperature'] = $temperature;
        }
        $qb = $this->_em->createQueryBuilder();

        $qb
            ->select('
                DISTINCT p.code, p.dureeVieJours as dureeDeVie, p.dlc as dlcProduit, p.tvaCode as tva,
                op.id as ligneCommandId,
                op.denominationProduitBase,
                op.poidsVariable as poid_variable,
                op.saleUnity as sale_unity,
                op.pcb,
                op.ean13,
                op.temperature,
                op.marketingCode,
                op.natureCode,
                pr.code as promotion,
                op.moq,
                op.volumeUnitaire as volumeColis,
                op.poidsUnitaire as poidsColis, ' .
                ($stockColis ? ' st.stockTheoriqueColis as stock, ' : ' st.stockTheoriqueUc as stock, ') .
                'f3.code as sf,
                f3.libelle as sf_fr,
                f2.code as ry,
                f2.libelle as ry_fr,
                m.libelle as marque,
                op.quantite,' .
                ' ROUND(op.prixUnitaire, 2) as unitPriceFrom,' .
                ($multiplyByPcb ? ' ROUND(op.prixUnitaire * op.quantite * op.pcb, 2) as totalPrice,' : ' ROUND(op.prixUnitaire * op.quantite, 2) as totalPrice,') .
                ' ROUND(op.prixUnitaire * op.pcb, 2) as colisPrice')
            ->addSelect('pm.produitMeti')
            ->from('SogedialSiteBundle:Commande', 'o');
        $qb
            ->leftJoin('SogedialSiteBundle:LigneCommande', 'op',
                'WITH', $panier ? 'op.commande = o' : 'op.commande = o.parent')
            ->leftJoin('SogedialSiteBundle:Promotion', 'pr',
                'WITH', $panier ? 'op.commande = o' : 'pr.code = op.promotion')
            ->leftJoin('SogedialSiteBundle:HistoriqueLigneCommande', 'hlc',
                'WITH', 'hlc.ligneCommande = op')
            ->leftJoin('op.produit', 'p')
            ->leftJoin('op.marque', 'm')
            ->leftJoin('op.famille', 'f3')
            ->leftJoin('p.rayon', 'f2')
            ->leftJoin('SogedialSiteBundle:Stock', 's',
                'WITH', 's.produit = p')
            ->leftJoin('p.stock', 'st')
            ->leftJoin('SogedialSiteBundle:ProduitMeti', 'pm',
                'WITH', "pm.produit = p AND pm.client = '" . $codeClientOrigine . "'")
            ->where('o.id = :orderId')
            ->andWhere('p.actif=1')
            ->andWhere('op.quantite > 0');
        if (!$panier) {
            $qb
                ->andWhere('o.temperatureCommande = :temperature')
                ->andWhere('op.temperatureProduit = :temperature');
        } else {
            $qb
                ->andWhere($checkStock);
        }
        $qb
            ->andWhere('p.code = st.produit')
            ->andWhere('op.actif=1')
            ->setParameters($params)
            ->addOrderBy('f3.libelle', 'ASC')
            ->addOrderBy('op.denominationProduitBase', 'ASC');

        $query = $qb->getQuery();
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, '\Sogedial\SiteBundle\Service\UseIndexWalker');
        $query->setHint(UseIndexWalker::HINT_USE_INDEX, 'commande_idx');

        $result['result'] = $query->getScalarResult();
        $result['tree'] = $this->getOrderProductTree($result['result']);

        return $result;

    }

    public function getOrderProductTree($orderProductsList)
    {
        $keys = ['sf'];

        $test = function ($level, $keys, $item) use (&$test) {
            if (!$key = array_shift($keys)) {
                return [];
            }
            $id = $item[$key];

            if (empty($level[$id])) {
                $level[$id] = [
                    'id' => $id,
                    'class' => self::slug($item[$key . '_fr']),
                    'fr' => $item[$key . '_fr'],
                    'children' => []
                ];
            }
            $levelNode = &$level[$id];
            $levelNode['children'] = $test($levelNode['children'], $keys, $item);

            return $level;
        };

        $tree = array_reduce($orderProductsList, function ($memo, $item) use (&$test, $keys) {
            return $test($memo, $keys, $item);
        }, []);

        return $tree;
    }

    /**
     * @param $text
     * @return mixed|string
     */
    static public function slug($text)
    {
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);
        $text = trim($text, '-');
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = strtolower($text);
        $text = preg_replace('~[^-\w]+~', '', $text);

        return empty($text) ? 'n-a' : $text;
    }

    /**
     * @param $orderId
     * @param $pcb calcul le total avec le pcb ou non
     * @return mixed
     * @throws NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getOrderTotalAmount($orderId, $pcb = null)
    {
        $qb = $this->_em->createQueryBuilder();

        $expr = ($pcb) ? "op.prixUnitaire * op.quantite" : "op.prixUnitaire * op.quantite * p.pcb";
        $checkStock = ($pcb) ? "s.stockTheoriqueUc != 0" : "s.stockTheoriqueColis != 0";

        $qb
            ->select('SUM(ROUND(' . $expr . ', 2)) as totalAmount')
            ->from('SogedialSiteBundle:Commande', 'o');
        $qb
            ->leftJoin('SogedialSiteBundle:LigneCommande', 'op',
                'WITH', 'op.commande = o')
            ->leftJoin('op.produit', 'p')
            ->leftJoin('SogedialSiteBundle:Stock', 's',
                'WITH', 's.produit = p')
            ->where('o.id = :orderId')
            ->andWhere($checkStock)
            ->andWhere('p.actif=1');

        $qb->setParameter('orderId', $orderId);
        return $qb->getQuery()->getSingleResult();
    }

    public function getOrderTotalVolumeWeight($orderId)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb
            ->select('SUM(ROUND(c.poidsBrutColis * op.quantite, 2)) as poidsTotal,
                        SUM(ROUND(c.volumeColis * op.quantite, 4)) as volumeTotal ')
            ->from('SogedialSiteBundle:Commande', 'o');
        $qb
            ->leftJoin('SogedialSiteBundle:LigneCommande', 'op',
                'WITH', 'op.commande = o')
            ->leftJoin('op.produit', 'p')
            ->leftJoin('SogedialSiteBundle:Colis', 'c',
                'WITH', 'c.produit = p')
            ->where('o.id = :orderId')
            ->andWhere('p.actif=1')
        ;

        $qb->setParameter('orderId', $orderId);

        return $qb->getQuery()->getSingleResult();
    }

    public function getOrderTotalVolumeWeightByTemp($orderId, $temp)
    {
        $qb = $this->_em->createQueryBuilder();


        $qb
            ->select('SUM(ROUND(c.poidsBrutColis * op.quantite, 2)) as poidsTemp, 
                        SUM(ROUND(c.volumeColis * op.quantite, 4)) as volumeTemp ')
            ->from('SogedialSiteBundle:Commande', 'o');
        $qb
            ->leftJoin('SogedialSiteBundle:LigneCommande', 'op',
                'WITH', 'op.commande = o')
            ->leftJoin('op.produit', 'p')
            ->leftJoin('SogedialSiteBundle:Colis', 'c',
                'WITH', 'c.produit = p')
            ->where('o.id = :orderId')
            ->andWhere('op.temperatureProduit = :temp')
            ->andWhere('p.actif=1')
        ;

        $qb->setParameter('orderId', $orderId);
        $qb->setParameter('temp', $temp);

        return $qb->getQuery()->getSingleResult();

    }

    public function getOrderCount($orderId, $temperature)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb
            ->select('COUNT(op.quantite) as countResult')
            ->from('SogedialSiteBundle:Commande', 'o')
            ->leftJoin('SogedialSiteBundle:LigneCommande', 'op', 'WITH', 'op.commande = o')
            ->leftJoin('op.produit', 'p')
            ->where('o.id = :orderId')
            ->andWhere('p.actif=1')
            ->andWhere('p.temperature= :temperature')
            ->andWhere('op.quantite>0');

        $qb->setParameter('orderId', $orderId);
        $qb->setParameter('temperature', $temperature);

        return $qb->getQuery()->getSingleResult();
    }

    public function getExcelRecapByOrder($orderId)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb
            ->select('DISTINCT p.code, p.denominationProduitBase, p.dlcMoyenne, p.ndp, p.pcb, p.ean13, p.temperature, op.quantite, op.prixUnitaire')
            ->from('SogedialSiteBundle:Commande', 'o');

        $qb
            ->leftJoin('SogedialSiteBundle:LigneCommande', 'op',
                'WITH', 'op.commande = o')
            ->leftJoin('op.produit', 'p')
            ->where('o.id = :orderId')
            ->andWhere('p.actif=1');


        $qb->setParameter('orderId', $orderId);
        return $qb->getQuery()->getScalarResult();
    }


    public function getProductsByOrder($order)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb
            ->select('p as produit')
            ->from('SogedialSiteBundle:Produit', 'p')
            ->addSelect('op.quantite')
            ->addSelect('op.prixUnitaire')
            ->addSelect('c.poidsBrutUVC, c.volumeColis');

        $qb
            ->leftJoin('SogedialSiteBundle:LigneCommande', 'op',
                'WITH', 'op.produit = p')
            ->leftJoin('p.colis', 'c')
            ->leftJoin('op.commande', 'o')
            ->where('o.id = :order')
            ->andWhere('p.actif=1');


        $qb->setParameter('order', $order);


        return $qb->getQuery()->getResult();

    }

    public function getProduits()
    {
        $qb = $this->createQueryBuilder('p');

        $qb->add('select', 'p')
            ->add('from', 'SogedialSiteBundle:Produit p')
            ->join('p.marque', 'm')
            ->join('p.departement', 'd')
            ->join('p.rayon', 'r')
            ->join('p.famille', 'f');
        try {
            return $qb->getQuery()->getArrayResult();
        } catch (NoResultException $e) {
            return $e->getMessage();
        }
    }

    /**
     * @return array|string
     */
    public function getCatalogueProduit($temperature)
    {
        $qb = $this->createQueryBuilder('p');

        $qb->add('select', 'p.code, p.denominationProduitBase, p.pcb, p.ean13, p.dlcMoyenne, p.marketingCode, m.libelle, t.prixHt, l.quantite as quantity, ROUND(t.prixHt * l.quantite * p.pcb, 2) as valeur')
            ->add('from', 'SogedialSiteBundle:Produit p')
            ->leftJoin('p.marque', 'm')
            ->leftJoin('p.famille', 'f3')
            ->leftJoin('p.rayon', 'f2')
            ->leftJoin('p.secteur', 'f1')
            ->leftJoin('p.tarifs', 't')
            ->leftJoin('p.lignes', 'l')
            ->Where('p.actif = 1')
            ->andWhere('p.temperature = :temperature')
            ->andWhere('p.code = t.produit')
            ->orderBy('p.denominationProduitBase', 'ASC')
            ->setParameter('temperature', $temperature)
            ->groupBy('p.ean13');

        try {
            return $qb->getQuery()->getArrayResult();
        } catch (NoResultException $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param $temperature
     * @return mixed|string
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getProduitWithoutSourceNumber($temperature)
    {
        $params = array(
            'temperature' => $temperature
        );

        $qb = $this->createQueryBuilder('p');

        $qb->add('select', 'count(p.code) as nbrProduitWithoutSource')
            ->add('from', 'SogedialSiteBundle:Produit p')
            ->Where('p.actif = 1')
            ->andWhere('p.temperature = :temperature')
            ->setParameters($params);

        try {
            return $qb->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param $temperature
     * @return mixed|string
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getAllProduitNumber($temperature)
    {
        $params = array(
            'temperature' => $temperature
        );

        $qb = $this->createQueryBuilder('p');

        $qb->add('select', 'count(p.code) as nbrAllProduit')
            ->add('from', 'SogedialSiteBundle:Produit p')
            ->Where('p.actif = 1')
            ->andWhere('p.temperature = :temperature')
            ->setParameters($params);

        try {
            return $qb->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return $e->getMessage();
        }
    }

    /**
     * @return array|string
     */
    public function getCatalogueProduitWithoutSource($temperature)
    {
        $params = array(
            'temperature' => $temperature
        );

        $qb = $this->createQueryBuilder('p');

        $qb->add('select', 'p.code, p.denominationProduitBase, p.pcb, p.ean13, p.dlcMoyenne, p.marketingCode, p.temperature, m.libelle')
            ->add('from', 'SogedialSiteBundle:Produit p')
            ->leftJoin('p.marque', 'm')
            ->leftJoin('p.famille', 'f3')
            ->leftJoin('p.rayon', 'f2')
            ->leftJoin('p.secteur', 'f1')
            ->Where('p.actif = 1')
            ->andWhere('p.temperature = :temperature')
            ->orderBy('p.denominationProduitBase', 'ASC')
            ->setParameters($params)
            ->groupBy('p.ean13');

        $allProducts = $qb->getQuery()->getArrayResult();

        return $allProducts;

    }

    /**
     * @param $temperature
     * @return array
     */
    public function getCatalogueAllProduit($temperature)
    {
        $params = array(
            'temperature' => $temperature
        );

        $qb = $this->createQueryBuilder('p');

        $qb->add('select', 'p.code, p.denominationProduitBase, p.pcb, p.ean13, p.dlcMoyenne, p.marketingCode, m.libelle')
            ->add('from', 'SogedialSiteBundle:Produit p')
            ->leftJoin('p.marque', 'm')
            ->leftJoin('p.famille', 'f3')
            ->leftJoin('p.rayon', 'f2')
            ->leftJoin('p.secteur', 'f1')
            ->Where('p.actif = 1')
            ->andWhere('p.temperature = :temperature')
            ->orderBy('p.denominationProduitBase', 'ASC')
            ->setParameters($params)
            ->groupBy('p.ean13');

        $allProducts = $qb->getQuery()->getArrayResult();

        return $allProducts;

    }

    /**
     * @param $limit
     * @return array
     */
    public function getCatalogueAllProduitToAdmin($limit)
    {
        $qb = $this->createQueryBuilder('p');

        $qb->add('select', 'p.code, p.denominationProduitBase, p.pcb, p.ean13, p.dlcMoyenne, p.marketingCode, m.libelle, p.natureCode, t.prixHt, st.stockTheoriqueColis as stock, p.natureCode, ROUND(t.prixHt * p.pcb, 2) as total')
            ->add('from', 'SogedialSiteBundle:Produit p')
            ->leftJoin('p.marque', 'm')
            ->leftJoin('p.famille', 'f3')
            ->leftJoin('p.rayon', 'f2')
            ->leftJoin('p.secteur', 'f1')
            ->leftJoin('p.tarifs', 't')
            ->leftJoin('p.stock', 'st')
            ->Where('p.actif = 1')
            ->orderBy('p.denominationProduitBase', 'ASC')
            ->setMaxResults($limit)
            ->groupBy('p.ean13');

        $allProducts = $qb->getQuery()->getArrayResult();

        return $allProducts;

    }

    public function getProduitWithoutPhoto($limit)
    {
        $qb = $this->createQueryBuilder('p');
        if (is_integer($limit)) {
            $qb->add('select', 'p.code, p.denominationProduitBase, p.pcb, p.ean13, p.dlcMoyenne, p.marketingCode, m.libelle, p.natureCode, t.prixHt, st.stockTheoriqueColis as stock, p.natureCode, ROUND(t.prixHt * p.pcb, 2) as total, p.temperature')
                ->add('from', 'SogedialSiteBundle:Produit p')
                ->leftJoin('p.marque', 'm')
                ->leftJoin('p.famille', 'f3')
                ->leftJoin('p.rayon', 'f2')
                ->leftJoin('p.secteur', 'f1')
                ->leftJoin('p.tarifs', 't')
                ->leftJoin('p.stock', 'st')
                ->Where('p.actif = 1')
                ->orderBy('p.denominationProduitBase', 'ASC')
                ->setMaxResults($limit)
                ->groupBy('p.ean13');
        } elseif ($limit == 'ALL') {
            $qb->add('select', 'p.code, p.denominationProduitBase, p.pcb, p.ean13, p.dlcMoyenne, p.marketingCode, m.libelle, p.natureCode, t.prixHt, st.stockTheoriqueColis as stock, p.natureCode, ROUND(t.prixHt * p.pcb, 2) as total, p.temperature')
                ->add('from', 'SogedialSiteBundle:Produit p')
                ->leftJoin('p.marque', 'm')
                ->leftJoin('p.famille', 'f3')
                ->leftJoin('p.rayon', 'f2')
                ->leftJoin('p.secteur', 'f1')
                ->leftJoin('p.tarifs', 't')
                ->leftJoin('p.stock', 'st')
                ->Where('p.actif = 1')
                ->orderBy('p.denominationProduitBase', 'ASC')
                ->groupBy('p.ean13');
        }
        $allProducts = $qb->getQuery()->getArrayResult();

        return $allProducts;

    }

    public function getHierarchical($valeur, $tarif, $code_entreprise, $preCommandeMode, $tarifDegressifs = false)
    {

        $valuePreco = ($preCommandeMode ? 1 : 0);
        $filterRegion = $code_entreprise[0] . "-%";
        $params = array(
            "preco" => $valuePreco,
            "actif" => 1,
            "valeur" => $valeur,
            "region" => $filterRegion
        );

        $qb = $this->createQueryBuilder('p');
        $qb->add('select', 'count(DISTINCT p.code) as counter, fam.code as famille, fam.libelle as famille_fr, ryn.code as rayon, ryn.libelle as rayon_fr, sect.code as secteur, sect.libelle as secteur_fr')
            ->add('from', 'SogedialSiteBundle:Produit p')
            ->leftJoin('p.assortiments', 'ass')
            ->innerJoin('SogedialSiteBundle:Secteur', 'sect',
                'WITH', 'p.secteur = sect AND sect.code LIKE :region')
            ->innerJoin('SogedialSiteBundle:Rayon', 'ryn',
                'WITH', 'ryn.secteur = sect AND ryn.code LIKE :region')
            ->innerJoin('SogedialSiteBundle:Famille', 'fam',
                'WITH', 'fam.rayon = ryn AND fam.code LIKE :region')
            ->innerJoin('SogedialSiteBundle:Stock', 'stk',
                'WITH', 'stk.produit = p')
            ->Where('p.actif = :actif')
            ->andWhere('p.code = ass.produit')
            ->andWhere('p.famille = fam')
            ->andWhere('p.rayon = ryn')
            ->andWhere('p.secteur = sect')
            ->andWhere('p.preCommande = :preco')
            ->andWhere('ass.valeur = :valeur')
            ->addOrderBy('sect.libelle', 'ASC')
            ->addOrderBy('ryn.libelle', 'ASC')
            ->addOrderBy('fam.libelle', 'ASC')
            ->groupBy('fam.code');


        if ($tarifDegressifs) {
            $qb->innerJoin('SogedialSiteBundle:Degressif', 'deg',
                'WITH', "deg.produit = p.code");
        } else {
            $params["tarif"] = $tarif;
            $qb->innerJoin('SogedialSiteBundle:Tarif', 'trf',
                'WITH', "trf.produit = p AND (trf.enseigne = :tarif OR trf.tarification = :tarif ) ");
        }

        $qb->setParameters($params);
        $result = $qb->getQuery()->getArrayResult();

        return $result;
    }

    /**
     * @param $valeur
     * @param $tarif
     * @param $code_entreprise
     * @param $preCommandeMode
     * @param bool $tarifDegressifs
     * @return array
     */
    public function getHierarchicalForSogedial($valeur, $tarif, $code_entreprise, $preCommandeMode, $tarifDegressifs = false)
    {

        $valuePreco = ($preCommandeMode ? 1 : 0);
        $filterRegion = $code_entreprise[0] . "-%";
        $params = array(
            "preco" => $valuePreco,
            "actif" => 1,
            "valeur" => $valeur,
            "region" => $filterRegion
        );

        $qb = $this->createQueryBuilder('p');
        $qb->add('select', 'count(DISTINCT p.code) as counter, fam.code as famille, fam.libelle as famille_fr, ryn.code as rayon, ryn.libelle as rayon_fr, sect.code as secteur, sect.libelle as secteur_fr')
            ->add('from', 'SogedialSiteBundle:Produit p')
            ->leftJoin('p.assortiments', 'ass')
            ->innerJoin('SogedialSiteBundle:Secteur', 'sect',
                'WITH', 'p.secteur = sect AND sect.code LIKE :region')
            ->innerJoin('SogedialSiteBundle:Rayon', 'ryn',
                'WITH', 'ryn.secteur = sect AND ryn.code LIKE :region')
            ->innerJoin('SogedialSiteBundle:Famille', 'fam',
                'WITH', 'fam.code LIKE :region')
            ->innerJoin('SogedialSiteBundle:Stock', 'stk',
                'WITH', 'stk.produit = p')
            ->Where('p.actif = :actif')
            ->andWhere('p.code = ass.produit')
            ->andWhere('p.famille = fam')
            ->andWhere('p.secteur = sect')
            ->andWhere('p.preCommande = :preco')
            ->andWhere('ass.valeur = :valeur')
            ->addOrderBy('sect.libelle', 'ASC')
            ->addOrderBy('ryn.libelle', 'ASC')
            ->addOrderBy('fam.libelle', 'ASC')
            ->groupBy('fam.code');


        if ($tarifDegressifs) {
            $qb->innerJoin('SogedialSiteBundle:Degressif', 'deg',
                'WITH', "deg.produit = p.code");
        } else {
            $params["tarif"] = $tarif;
            $qb->innerJoin('SogedialSiteBundle:Tarif', 'trf',
                'WITH', "trf.produit = p AND (trf.enseigne = :tarif OR trf.tarification = :tarif ) ");
        }

        $qb->setParameters($params);
        $result = $qb->getQuery()->getArrayResult();

        return $result;
    }

    /**
     * @param $valeur
     * @param $tarif
     * @param $code_entreprise
     * @param $preCommandeMode
     * @return mixed
     */
    public function getNouveauteCompteur($valeur, $tarif, $code_entreprise, $preCommandeMode, $tarifDegressifs = false)
    {
        $valuePreco = ($preCommandeMode ? 1 : 0);
        $filterRegion = "'" . $code_entreprise[0] . "-%'";

        $params = array(
            "preco" => $valuePreco,
            "actif" => 1,
            "valeur" => $valeur
        );


        $qb = $this->createQueryBuilder('p');
        $qb->add('select', 'count(DISTINCT p.code) as counter')
            ->add('from', 'SogedialSiteBundle:Produit p')
            ->leftJoin('p.assortiments', 'ass')
            ->innerJoin('SogedialSiteBundle:Stock', 'stk',
                'WITH', 'stk.produit = p')
            ->Where('p.actif = :actif')
            ->andWhere("p.natureCode = 'NOUVEAUTE' ")
            ->andWhere('p.code = ass.produit')
            ->andWhere('p.preCommande = :preco')
            ->andWhere('ass.valeur = :valeur');

        if ($tarifDegressifs) {
            $qb->innerJoin('SogedialSiteBundle:Degressif', 'deg',
                'WITH', "deg.produit = p.code");
        } else {
            $params["tarif"] = $tarif;
            $qb->innerJoin('SogedialSiteBundle:Tarif', 'trf',
                'WITH', "trf.produit = p AND (trf.enseigne = :tarif OR trf.tarification = :tarif ) ");
        }

        $qb->setParameters($params);

        $result = current($qb->getQuery()->getArrayResult());

        return $result;
    }

    /**
     * @param $codeClient
     * @param $enseigne
     * @param $valeur
     * @param $tarif
     * @param $code_entreprise
     * @param $preCommandeMode
     * @return mixed
     */
    public function getPromotionCompteur($codeClient, $enseigne, $valeur, $tarif, $code_entreprise, $preCommandeMode, $tarifDegressifs = false)
    {
        $valuePreco = ($preCommandeMode ? 1 : 0);
        $filterRegion = "'" . $code_entreprise[0] . "-%'";

        $params = array(
            "preco" => $valuePreco,
            "actif" => 1,
            "valeur" => $valeur,
            'codeClient' => $codeClient,
            'code_entreprise' => $code_entreprise,
            'enseigne' => $enseigne,
            'now' => new \DateTime('now')
        );

        $qb = $this->createQueryBuilder('p');
        $qb->add('select', 'count(DISTINCT p.code) as counter')
            ->add('from', 'SogedialSiteBundle:Produit p')
            ->leftJoin('p.assortiments', 'ass')
            ->Where("p.actif = :actif")
            ->innerJoin('SogedialSiteBundle:Stock', 'stk',
                'WITH', 'stk.produit = p')
            ->innerJoin('SogedialSiteBundle:Promotion', 'prom',
                'WITH', "prom.produit = p
                AND (prom.client = :codeClient OR prom.enseigne = :enseigne) AND prom.entreprise = :code_entreprise
                AND prom.codeTypePromo != 'TX'
                AND prom.dateDebutValidite <= :now
                AND prom.dateFinValidite >= :now ")
            ->andWhere('p.code = ass.produit')
            ->andWhere('p.preCommande = :preco')
            ->andWhere('ass.valeur = :valeur');

        if ($tarifDegressifs) {
            $qb->innerJoin('SogedialSiteBundle:Degressif', 'deg',
                'WITH', "deg.produit = p.code");
        } else {
            $params["tarif"] = $tarif;
            $qb->innerJoin('SogedialSiteBundle:Tarif', 'trf',
                'WITH', "trf.produit = p AND (trf.enseigne = :tarif OR trf.tarification = :tarif ) ");
        }

        $qb->setParameters($params);
        $result = current($qb->getQuery()->getArrayResult());

        return $result;
    }

    //TODO : fusioner getHierarchical et getHierarchicalToAdmin en présant le mode (commercial ou client)
    public function getHierarchical2($temperature, $valeur, $mode)
    {
        if ($mode == 'MODE_CLIENT') {
            $params = array(
                'temperature' => $temperature,
                'valeur' => $valeur
            );

            $qb = $this->createQueryBuilder('p');

            $qb->add('select', 'COUNT(p.code) as counter, f3.code as ssf, f3.libelle as ssf_fr, f2.code as sf, f2.libelle as sf_fr, f1.code as f, f1.libelle as f_fr')
                ->add('from', 'SogedialSiteBundle:Produit p')
                ->leftJoin('p.assortiments', 'ass')
                ->leftJoin('p.famille', 'f3')
                ->leftJoin('p.rayon', 'f2')
                ->leftJoin('p.secteur', 'f1')
                ->Where('p.actif = 1')
                ->andWhere('p.code = ass.produit')
                ->andWhere('p.temperature = :temperature')
                ->andWhere('f3.rayon = f2.code')
                ->andWhere('f2.secteur = f1.code')
                ->andWhere('ass.valeur = :valeur')
                ->setParameters($params)
                ->addOrderBy('f1.libelle', 'ASC')
                ->addOrderBy('f2.libelle', 'ASC')
                ->addOrderBy('f3.libelle', 'ASC')
                ->groupBy('f3');
        } elseif ($mode == 'MODE_COMMERCIAL') {

            $qb = $this->createQueryBuilder('p');

            $qb->add('select', 'COUNT(p.code) as counter, f3.code as ssf, f3.libelle as ssf_fr, f2.code as sf, f2.libelle as sf_fr, f1.code as f, f1.libelle as f_fr')
                ->add('from', 'SogedialSiteBundle:Produit p')
                ->leftJoin('p.assortiments', 'ass')
                ->leftJoin('p.famille', 'f3')
                ->leftJoin('p.rayon', 'f2')
                ->leftJoin('p.secteur', 'f1')
                ->Where('p.actif = 1')
                ->andWhere('p.code = ass.produit')
                ->andWhere('f3.rayon = f2.code')
                ->andWhere('f2.secteur = f1.code')
                //->andWhere('ass.valeur = :valeur')
                ->addOrderBy('f1.libelle', 'ASC')
                ->addOrderBy('f2.libelle', 'ASC')
                ->addOrderBy('f3.libelle', 'ASC')
                ->groupBy('f3');

        }
        $result = $qb->getQuery()->getArrayResult();

        return $result;
    }

    /**
     * @param $codeSousFamille
     * @return array
     */
    public function getAllProductsOfSousFamilleAndEntrepriseInAssortiment($codeSousFamille, $codeEntreprise, $codeAssortiment)
    {
        $qb = $this->_em->createQueryBuilder();

        $result = $qb
            ->select('p.code, p.denominationProduitBase as libelle')
            ->from('SogedialSiteBundle:Produit', 'p')
            ->leftJoin('SogedialSiteBundle:Assortiment', 'a',
                \Doctrine\ORM\Query\Expr\Join::WITH, 'a.produit = p.code')
            ->where('p.sousFamille = :codeSousFamille')
            ->andWhere('p.entreprise = :codeEntreprise')
            ->andWhere('a.valeur = :codeAssortiment')
            ->orderBy('libelle', 'ASC')
            ->setParameter('codeAssortiment', $codeAssortiment)
            ->setParameter('codeSousFamille', $codeSousFamille)
            ->setParameter('codeEntreprise', $codeEntreprise)
            ->getQuery()
            ->getArrayResult();

        return $result;
    }

    public function getProductByCode($codeProduit)
    {
        $pParams = array(
            'codeProduit' => $codeProduit
        );

        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.code = :codeProduit');

        $qb->setParameters($pParams);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param $orderId
     * @param $multiplyByPcb
     * @param $stockColis
     * @param string $codeClient
     * @return array
     */
    public function getPanierListProducts($orderId, $multiplyByPcb = true, $stockColis, $codeClient = "")
    {
        $conn = $this->getEntityManager()->getConnection();
        $codeClientOrigine = $this->getClientCodeOrigine($codeClient);

        $sql =
            'SELECT DISTINCT  p.code_produit as code, p.poids_variable as poid_variable, p.sale_unity, p.denomination_produit_base denominationProduitBase, p.pcb, p.ean13_produit as ean13, p.temperature, p.marketing_code as marketingCode, p.nature_code as natureCode, p.duree_vie_jours as dureeDeVie, p.dlc_produit as dlcProduit, ent.raison_sociale as entreprise,
            f3.code_famille as sf, f3.libelle as sf_fr, m.libelle as marque, op.quantite, ROUND (op.prix_unitaire, 2) as prixHt, ROUND(op.prix_unitaire * p.pcb, 2) as colisPrice,
            pm.produit_meti as produitMeti, cls.volumeColis, cls.poidsBrutColis as poidsColis, cpm.moq_quantite as moq_client, p.actif as etatProduit,
        ';

        if ($stockColis) {
            $sql .= ' st.stock_theorique_colis as stock, ';
        } else {
            $sql .= ' st.stock_theorique_uc as stock, ';
        }

        if ($multiplyByPcb) {
            $sql .= ' ROUND(op.prix_unitaire * op.quantite * p.pcb, 2) as totalPrice ';
        } else {
            $sql .= ' ROUND(op.prix_unitaire * op.quantite, 2) as totalPrice ';
        }

        $sql .= " FROM commande o
                  LEFT JOIN ligneCommande op ON op.commande_id = o.id
                  LEFT JOIN entreprise ent ON ent.code_entreprise = o.code_entreprise
                  LEFT JOIN produit p ON p.code_produit = op.code_produit
                  LEFT JOIN client c ON c.code_client = '$codeClient'
                  LEFT JOIN client_produit_moq cpm ON cpm.code_client = c.code_client AND cpm.code_produit = p.code_produit
                  LEFT JOIN produit_meti pm ON pm.code_produit = p.code_produit AND pm.code_client = '$codeClientOrigine'
                  LEFT JOIN colis cls ON cls.code_produit = p.code_produit
                    
                  LEFT JOIN marque m ON m.code_marque = p.code_marque
                  LEFT JOIN famille f3 ON f3.code_famille = p.code_famille
                  LEFT JOIN rayon f2 ON f2.code_rayon = p.code_rayon
                  LEFT JOIN stock st ON st.code_produit = p.code_produit
                  WHERE o.id = $orderId
                  AND p.code_produit = st.code_produit
                  ORDER BY f3.libelle ASC, p.denomination_produit_base ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute();

        $results = $stmt->fetchAll();

       return $results;

    }

    /**
     * @param $codeClient
     * @return string
     *
     */
    private function getClientCodeOrigine($codeClient)
    {
        //Quick fix probleme code client original precommande
        $codeClientOrigine = $codeClient;
        if ($codeClient != "") {
            $qbEntreprise = $this->_em->createQueryBuilder();
            $codeEntrepriseClient = explode("-", $codeClient)[0];

            $qbEntreprise->select('e')
                ->from('SogedialSiteBundle:Entreprise', 'e')
                ->where("e.code = :entreprise")
                ->setParameter('entreprise', $codeEntrepriseClient);

            $entreprise = current($qbEntreprise->getQuery()->getResult());
            if ($entreprise->getEntrepriseParent() != null) {
                $codeClientOrigine = $entreprise->getEntrepriseParent()->getCode() . "-" . explode("-", $codeClient)[1];
            }
        }
        return $codeClientOrigine;
    }
}
