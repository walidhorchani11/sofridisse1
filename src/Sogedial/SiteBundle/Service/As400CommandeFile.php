<?php

namespace Sogedial\SiteBundle\Service;

use Doctrine\ORM\EntityManager;
use Sogedial\SiteBundle\Entity\Commande;
use Sogedial\SiteBundle\Service\MultiSiteService;

class As400CommandeFile
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var \Sogedial\SiteBundle\Service\MultiSiteService
     */
    protected $multisiteService;

    /**
     * As400CommandeFile constructor.
     * @param $rootDir
     * @param EntityManager $entityManager
     * @param \Sogedial\SiteBundle\Service\MultiSiteService $multisiteService
     */
    public function __construct($rootDir, EntityManager $entityManager, MultiSiteService $multisiteService)
    {
        $this->webRoot = realpath($rootDir . '/../web');
        $this->em = $entityManager;
        $this->multisiteService = $multisiteService;
    }

    /**
     * @param $products
     * @param $clientInfos
     * @param $panierId
     * @param $entrepriseInfos
     * @param $comment
     * @param bool $isPreCommande
     */
    public function handleFile($products, $clientInfos, $panierId, $entrepriseInfos, $comment, $isPreCommande = false)
    {
        $arrayTmp = array_keys($products);
        $split = explode("-", $entrepriseInfos['codeClient']);
        $codeEntrepriseClient = substr($split[0], 0, 3);
        $valeurEntrepriseClient = substr($split[0], 1, 2);

        foreach ($arrayTmp as $temperature) {
            if (count($products[$temperature]) > 0) {
                $orderByTemperatureObj = $this->em->getRepository('SogedialSiteBundle:Commande')->getByParentTemperatureOrderStatus($panierId, $temperature, 'STATUS_APPROVED');
                if ($orderByTemperatureObj instanceof Commande) {
                    $listRegion = $this->multisiteService->getFallbackRegionCsv();

                    foreach ( $listRegion as $region) {
                        if(!is_dir(sprintf('%s%s', 'command/region', $region[0] ))) {
                            mkdir(sprintf('%s%s', 'command/region',$region[0] ), 0777, true);
                        }
                    }

                    if (strlen($codeEntrepriseClient) != 3 && strlen($codeEntrepriseClient) != 4) die("Code entreprise invalide. La commande n'a pas été envoyée. Veuillez contacter votre commercial.");

                    if($isPreCommande === true){
                        $preCommandRegionDirectory = sprintf('/%s/%s/%s', 'precommand', sprintf('%s%s', 'region', substr($codeEntrepriseClient, 0,1)), 'CD_WEB.C' );
                        $filePath = $this->webRoot . $preCommandRegionDirectory . $codeEntrepriseClient . substr($orderByTemperatureObj->getNumero(), -6);
                    } else {
                        $commandRegionDirectory = sprintf('/%s/%s/%s', 'command', sprintf('%s%d', 'region', substr($codeEntrepriseClient, 0,1)), 'CD_WEB.C' );
                        $filePath = $this->webRoot . $commandRegionDirectory . $codeEntrepriseClient . substr($orderByTemperatureObj->getNumero(), -6);
                    }


                    if (!is_dir(dirname($filePath))) {
                        mkdir(dirname($filePath) . '/', 0777, TRUE);
                    }

                    $file = fopen($filePath, "a+") or die("Unable to open file!");
                    $Commentaire = str_replace('-', ' ', $comment);

                    $ligneVendeur = sprintf('%s%s%s%s',
                        'A',
                        $valeurEntrepriseClient,
                        substr($orderByTemperatureObj->getNumero(), -3),
                        'A7'
                    );
                    // Pour obtenir les 80 caractères nécessaires pour la ligne
                    $enteteVendeur = $this->fullfilWithSpaceEighty($ligneVendeur);

                    fwrite($file, $this->getDernierCaractere($enteteVendeur));

                    $startsubfullfil = ($isPreCommande) ? 5 : 4;
                    $ligneClient = sprintf('%s%s%s%s%s',
                        'B',
                        $valeurEntrepriseClient,
                        substr($orderByTemperatureObj->getNumero(), -3),
                        $this->fullfilWithSpaceTeen(substr($clientInfos['code'],$startsubfullfil)),
                        substr($orderByTemperatureObj->getNumero(), -8)
                    );
                    if($isPreCommande === true){
                        $ligneClient[29] = '2';
                    }

                    // Pour obtenir les 80 caractères nécessaires pour la ligne
                    $enteteClient = $this->fullfilWithSpaceEighty($ligneClient);

                    fwrite($file, $this->getDernierCaractere($enteteClient));

                    foreach ($products[$temperature]['products'] as $product) {
                        $societe_site = $this->multisiteService->getMasterEnterprise();

                        if($societe_site == 'sofrigu') {
                            $quantiteUc = $product['quantite'];
                            $product['quantite'] = 0;
                        } else {
                            $quantiteUc = ($product['quantite'] * $product['pcb']);
                        }

                        $ligneArticle = sprintf('%s%s%s%s%s%s%s%s%s',
                            'D',
                            $valeurEntrepriseClient,
                            substr($orderByTemperatureObj->getNumero(), -3),
                            $this->fullfilWithSpaceThirteen(substr($product['code'], 4)),
                            $this->fullfilWithZerothree($product['quantite']),
                            $this->fullfilWithZeroNine($product['prixHt']),
                            'I',
                            'N',
                            $this->fullfilWithZeroNine(sprintf('%d%s', $quantiteUc, '000'))
                        );
                        // Pour obtenir les 80 caractères nécessaires pour la ligne
                        $ligneArticleArray = $this->fullfilWithSpaceEighty($ligneArticle);

                        fwrite($file, $this->getDernierCaractere($ligneArticleArray));
                    }

                    $ligneCommande = sprintf('%s%s%s%s%s%s',
                        'C',
                        $valeurEntrepriseClient,
                        substr($orderByTemperatureObj->getNumero(), -3),
                        ($orderByTemperatureObj->getDeliveryDate() !== NULL) ? $orderByTemperatureObj->getDeliveryDate()->format('dmY') : "  ", //TODO : dans le contexte as400, le format de la date doit être jjmmaaaa
                        $this->fullfilWithSpacefourty($Commentaire),
                        'L'
                    );
                    // Pour obtenir les 80 caractères nécessaires pour la ligne
                    $piedCommande = $this->fullfilWithSpaceEighty($ligneCommande);

                    fwrite($file, $this->getDernierCaractere($piedCommande));
                    fclose($file);
                }
            }

        }

    }

    /**
     * Remplit la valeur passée en paramètre avec des espaces si elle ne correspond pas au critère
     * @param $value
     * @return mixed
     */
    protected function fullfilWithSpaceEighty($value)
    {
        $maxStrX = 80;

        if (strlen($value) < $maxStrX) {
            $value = str_pad($value, $maxStrX);
        }
        return $value;
    }

    public function getDernierCaractere($carac)
    {
        return $carac . "\x0d\x0a";
    }

    /**
     * Remplit la valeur passée en paramètre avec des espaces si elle ne correspond pas au critère
     * @param $value
     * @return mixed
     */
    protected function fullfilWithSpaceTeen($value)
    {
        $minimumStrX = 10;

        if (strlen($value) < $minimumStrX) {
            $value = str_pad($value, $minimumStrX);
        }
        return $value;
    }

    /**
     * Remplit la valeur passée en paramètre avec des espaces si elle ne correspond pas au critère
     * @param $value
     * @return mixed
     */
    protected function fullfilWithSpaceThirteen($value)
    {
        $minimumStrX = 13;

        if (strlen($value) < $minimumStrX) {
            $value = str_pad($value, $minimumStrX);
        }
        return $value;
    }

    /**
     * Remplit la valeur passée en paramètre avec des zeros si elle ne correspond pas au critère
     * @param $value
     * @return mixed
     */
    protected function fullfilWithZerothree($value)
    {
        $minimumStrX = 3;

        if (strlen($value) < $minimumStrX) {
            $value = str_pad($value, $minimumStrX, '0', STR_PAD_LEFT);
        }
        return $value;
    }

    /**
     * Remplit la valeur passée en paramètre avec des zeros si elle ne correspond pas au critère
     * @param $value
     * @return mixed
     */
    protected function fullfilWithZeroNine($value)
    {
        $minimumStrX = 9;

        if (strlen($value) < $minimumStrX) {
            $value = str_pad(str_replace(".", "", $value), $minimumStrX, '0', STR_PAD_LEFT);
        }
        return $value;
    }

    /**
     * Remplit la valeur passée en paramètre avec des espaces si elle ne correspond pas au critère
     * @param $value
     * @return mixed
     */
    protected function fullfilWithSpacefourty($value)
    {
        $minimumStrX = 40;

        if (strlen($value) < $minimumStrX) {
            $value = str_pad($value, $minimumStrX);
        }
        return $value;
    }
}