<?php

namespace Sogedial\SiteBundle\Service;

use Doctrine\ORM\EntityManager;
use Sogedial\SiteBundle\Entity\Commande;
use Sogedial\SiteBundle\Entity\Client;
use Sogedial\SiteBundle\Entity\MailParams;
use Symfony\Component\HttpFoundation\RequestStack;
use Knp\Bundle\SnappyBundle\Snappy\LoggableGenerator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Dompdf\Dompdf;
use Dompdf\Options;
use Sogedial\SiteBundle\Service\Barcode;


class ExportService
{
    /**
     * @var \Liuggio\ExcelBundle\Factory
     */
    protected $phpexcel;
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var LoggableGenerator
     */
    protected $pdf;

    protected $templateEngine;

    protected $kernel;

    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var MultiSiteService
     */
    protected $ms;

    /**
     * ExportService constructor.
     * @param EntityManager $entityManager
     * @param RequestStack $requestStack
     * @param LoggableGenerator $pdf
     * @param $templateEngine
     * @param \Swift_Mailer $mailer
     * @param $kernel
     * @param $rootDir
     * @param MultiSiteService $ms
     * @param \Liuggio\ExcelBundle\Factory $phpexcel
     */
    public function __construct(EntityManager $entityManager, RequestStack $requestStack, LoggableGenerator $pdf, $templateEngine, \Swift_Mailer $mailer, $kernel, $rootDir, MultiSiteService $ms, \Liuggio\ExcelBundle\Factory $phpexcel)
    {
        $this->em = $entityManager;
        $this->requestStack = $requestStack;
        $this->pdf = $pdf;
        $this->templateEngine = $templateEngine;
        $this->mailer = $mailer;
        $this->kernel = $kernel;
        $this->webRoot = realpath($rootDir . '/../web');
        $this->ms = $ms;
        $this->phpexcel = $phpexcel;
    }

    /**
     * @param $order
     * @return mixed
     * @throws \PHPExcel_Exception
     */
    public function toExcelRecapExport($order)
    {
        $multiplyByPcb = !($this->ms->hasFeature('vente-par-unite'));
        $stockColis = $multiplyByPcb;
        $orderProducts = $this->em->getRepository('SogedialSiteBundle:Produit')->getRecapByOrderForOrderDetails($order->getId(), $order->getTemperatureCommande(), $multiplyByPcb, $stockColis, false);

        $phpExcelObject = $this->phpexcel->createPHPExcelObject('excel/Export_bdc_template.xlsx');

        $phpExcelObject->getProperties()->setCreator("SOGEDIAL EXPLOITATION")
            ->setLastModifiedBy("SOGEDIAL EXPLOITATION")
            ->setTitle("Bon de commande e-catalogue")
            ->setSubject("Liste des Produits")
            ->setDescription("Liste des Produits de la commande")
            ->setCategory("PRODUITS SOGEDIAL EXPLOITATION");

        $sheets['dry'] = $phpExcelObject->getSheet(0);
        $productRowstyle = $sheets['dry']->getStyle('B9:C9');
        $sheetInfos['dry']['numberTotalProduct'] = 0;


        $numberTotalProducts = count($orderProducts['result']);
        for ($i = 0; $i < $numberTotalProducts; $i++) {
            $splitCodeProduit = preg_split('/-/', $orderProducts['result'][$i]['code']);
            $trueCodeProduit = count($splitCodeProduit) >= 2 ? $splitCodeProduit[1] : $orderProducts['result'][$i]['code'];

            $sheetToUse = $sheets['dry'];
            $currentRow = $sheetInfos['dry']['numberTotalProduct']++;
            $sheetToUse->duplicateStyle($productRowstyle, 'B' . (9 + $currentRow) . ':C' . (9 + $currentRow));
            $sheetToUse->setCellValue('B' . (9 + $currentRow), $trueCodeProduit);
            $sheetToUse->setCellValue('C' . (9 + $currentRow), ($orderProducts['result'][$i]['pcb'] * $orderProducts['result'][$i]['quantite']));
            $sheetToUse->setCellValue('D' . (9 + $currentRow), ($orderProducts['result'][$i]['quantite']));
        }

        $phpExcelObject->setActiveSheetIndex(0);

        $writer = $this->phpexcel->createWriter($phpExcelObject, 'Excel2007');
        $response = $this->phpexcel->createStreamedResponse($writer);
        $response->headers->set('Content-Type', 'application/vnd.ms-excel; charset=utf-8');
        $response->headers->set(
            'Content-Disposition',
            'attachment; filename="Bbc_export".xlsx'
        );
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        return $response;
    }

    /**
     * @param $commande
     * @return Response
     */
    public function toPdfRecapExport($commande)
    {
        $multiplyByPcb = !($this->ms->hasFeature('vente-par-unite'));
        $stockColis = $multiplyByPcb;
        $orderProducts = $this->em->getRepository('SogedialSiteBundle:Produit')->getRecapByOrderForOrderDetails($commande->getId(), $commande->getTemperatureCommande(), $multiplyByPcb, $stockColis, false);
        $entrepriseInfos = $this->em->getRepository('SogedialSiteBundle:Commande')->getEntrepriseInfosForRecapByOrder($commande->getId());
        $clientInfos = $this->em->getRepository('SogedialSiteBundle:Commande')->getClientInfosForRecapByOrder($commande->getId());
        $total = (int)count($orderProducts['result']);
        $codeSociete = $commande->getEntreprise()->getCode();
        $products['sumColis'] = 0;
        $products['sumPrice'] = 0;
        $listProductsByFamille = array();
        $listFamille = array();

        for ($i = 0; $i < $total; $i++) {
            if($codeSociete !== '301') {
                $orderProducts['result'][$i]['totalPrice'] = bcmul(
                    $orderProducts['result'][$i]['quantite'] *
                    $orderProducts['result'][$i]['pcb'],
                    $orderProducts['result'][$i]['unitPriceFrom'], 2);
            }
            $products['products'][] = $orderProducts['result'][$i];
            $products['sumColis'] += $orderProducts['result'][$i]['quantite'];
            $products['sumPrice'] += $orderProducts['result'][$i]['totalPrice'];
        }

        $catalogueProductsByFamille = $this->getCatalogueProductTree($orderProducts['result']);

        foreach ($catalogueProductsByFamille as $productByFamille) {

            foreach ($productByFamille['children'] as $childre) {
                $listFamille[] = sprintf('%s / %s', $productByFamille['fr'], $childre['fr']);
            }

            for ($i = 0; $i < $total; $i++) {
                if ($orderProducts['result'][$i]['ry'] == $productByFamille['id']) {
                    $listProductsByFamille[] = $orderProducts['result'][$i];
                }
            }
        }

        $codeSociete = $commande->getEntreprise()->getCode();
        $commercialInfo = $this->em->getRepository('SogedialUserBundle:User')->getCommercialInformation($codeSociete);
        $logoPath = sprintf('%s/%s', $this->webRoot, 'images/logo-notification.png');
        $barecodePath = sprintf('%s/%s', $this->webRoot, 'uploads/pdf/attachment/');

        //TODO : generate list of barecode inside a specific directory
        $bareCodeResult = $this->generateBareCode($orderProducts['result']);
        $tvaCalculation = $this->calculateTva($orderProducts['result']);

        $html = $this->templateEngine->render('SogedialIntegrationBundle:Commande:elements/layout/commande-pdf.html.twig', array(
                'entrepriseInfos' => $entrepriseInfos,
                'clientInfos' => $clientInfos,
                'orderProducts' => $products,
                'orderNumber' => $commande->getNumero(),
                'orderId' => $commande->getId(),
                'dateDeLivraison' => $commande->getDeliveryDate(),
                'listProductsByFamille' => $listProductsByFamille,
                'listFamille' => $listFamille,
                'montantCommande' => $commande->getMontantCommande(),
                'commentaire' => $commande->getCommentaire(),
                'commercialInfo' => $commercialInfo,
                'poidsTotal' => $commande->getPoidsCommande(),
                'volumeTotal' => $commande->getVolumeCommande(),
                'logoPath' => $logoPath,
                'barecodePath' => $barecodePath,
                'tvaCalculation' => $tvaCalculation
            )
        );


        $options = new Options();
        $options->set('isRemoteEnabled', TRUE);

        $dompdf = new Dompdf($options);
        $contxt = stream_context_create([
            'ssl' => [
                'verify_peer' => FALSE,
                'verify_peer_name' => FALSE,
                'allow_self_signed'=> TRUE
            ]
        ]);
        $dompdf->setHttpContext($contxt);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $font = $dompdf->getFontMetrics()->getFont("helvetica", "bold");
        $dompdf->getCanvas()->page_text(532, 830, 'page {PAGE_NUM} sur {PAGE_COUNT}', $font, 5, array(0, 0, 0));

        $filename = sprintf('BDC-%s.pdf', $commande->getNumero());
        $output = $dompdf->stream($filename, array());


        return new Response($output,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ]
        );
    }

    /**
     * @param $order
     * @param $client
     * @return Response
     */
    public function generatePdfForOrder($order, $client)
    {
        $path = $this->webRoot . '/uploads/pdf/attachment/';

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $listProductsByFamille = array();
        $listFamille = array();

        $multiplyByPcb = !($this->ms->hasFeature('vente-par-unite'));
        $stockColis = $multiplyByPcb;
        $orderProducts = $this->em->getRepository('SogedialSiteBundle:Produit')->getRecapByOrderForOrderDetails($order->getId(), $order->getTemperatureCommande(), $multiplyByPcb, $stockColis);
        $products['sumColis'] = 0;
        $total = (int)count($orderProducts['result']);

        for ($i = 0; $i < $total; $i++) {
            $products['sumColis'] += $orderProducts['result'][$i]['quantite'];
        }

        $catalogueProductsByFamille = $this->getCatalogueProductTree($orderProducts['result']);

        foreach ($catalogueProductsByFamille as $productByFamille) {

            foreach ($productByFamille['children'] as $childre) {
                $listFamille[] = sprintf('%s / %s', $productByFamille['fr'], $childre['fr']);
            }

            for ($i = 0; $i < $total; $i++) {
                if ($orderProducts['result'][$i]['ry'] == $productByFamille['id']) {
                    $listProductsByFamille[] = $orderProducts['result'][$i];
                }
            }
        }

        $codeSociete = $order->getEntreprise()->getCode();
        $commercialInfo = $this->em->getRepository('SogedialUserBundle:User')->getCommercialInformation($codeSociete);
        $logoPath = sprintf('%s/%s', $this->webRoot, 'images/logo-notification.png');
        $barecodePath = sprintf('%s/%s', $this->webRoot, 'uploads/pdf/attachment/');

        //TODO : generate list of barecode inside a specific directory
        $bareCodeResult = $this->generateBareCode($orderProducts['result']);
        $tvaCalculation = $this->calculateTva($orderProducts['result']);

        $html = $this->templateEngine->render('SogedialIntegrationBundle:Commande:elements/layout/commande-pdf.html.twig', array(
                'entrepriseInfos' => $order->getEntreprise(),
                'clientInfos' => $client,
                'orderProducts' => $products,
                'orderNumber' => $order->getNumero(),
                'orderId' => $order->getId(),
                'listProductsByFamille' => $listProductsByFamille,
                'listFamille' => $listFamille,
                'dateDeLivraison' => $order->getDeliveryDate(),
                'montantCommande' => $order->getMontantCommande(),
                'commentaire' => $order->getCommentaire(),
                'commercialInfo' => $commercialInfo,
                'poidsTotal' => $order->getPoidsCommande(),
                'volumeTotal' => $order->getVolumeCommande(),
                'logoPath' => $logoPath,
                'barecodePath' => $barecodePath,
                'tvaCalculation' => $tvaCalculation
        ));

        $options = new Options();
        $options->set('isRemoteEnabled', TRUE);

        $dompdf = new Dompdf($options);
        $contxt = stream_context_create([
            'ssl' => [
                'verify_peer' => FALSE,
                'verify_peer_name' => FALSE,
                'allow_self_signed'=> TRUE
            ]
        ]);
        $dompdf->setHttpContext($contxt);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $font = $dompdf->getFontMetrics()->getFont("helvetica", "bold");
        $dompdf->getCanvas()->page_text(532, 830, 'page {PAGE_NUM} sur {PAGE_COUNT}', $font, 5, array(0, 0, 0));

        $filename = sprintf('BDC-%s.pdf', $order->getNumero());
        $filePath = sprintf('%s%s', $path, $filename);

        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath) . '/', 0777, TRUE);
        }
        $output = $dompdf->output();
        file_put_contents($filePath, $output);

        sleep(30);

        if (file_exists($filePath) == TRUE) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $orderNumber
     * @param $totalAmount
     * @param $clientName
     * @param $comment
     * @param $emailCommercial
     * @param $codeEntreprise
     * @return bool
     */
    public function sendFrancoMail($orderNumber, $totalAmount, $clientName, $comment, $emailCommercial, $codeEntreprise)
    {

        $mailParams = $this->getMailParams('FRANCO_MAIL', $codeEntreprise);

        if ($mailParams !== null) {
            $to = $mailParams->getTo();
            $from = $mailParams->getFrom();
            $cc = $mailParams->getMailCc();

            $target_path = $this->webRoot . '/uploads/pdf/attachment/';
            $filename = sprintf('BDC-%s.pdf', $orderNumber);
            $message = \Swift_Message::newInstance()
                ->setSubject($mailParams->getObject())
                ->setFrom($from)
                ->setTo($to)
                ->setCc($cc)
                ->setBody(
                    $this->templateEngine->render(
                        $mailParams->getTemplate(),
                        array(
                            'orderNumber' => $orderNumber,
                            'totalAmount' => $totalAmount,
                            'clientName' => $clientName,
                            'comment' => $comment
                        )
                    ),
                    'text/html'
                );

            if (file_exists(sprintf('%s%s', $target_path, $filename)) == TRUE) {
                $message->attach(\Swift_Attachment::fromPath(sprintf('%s%s', $target_path, $filename)));
            }

            if ($this->mailer->send($message)) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * @param $orderNumber
     * @param $totalAmount
     * @param $clientName
     * @param $comment
     * @param $emailClient
     * @return bool
     */
    public function sendFrancoMailForClient($orderNumber, $totalAmount, $clientName, $comment, $emailClient)
    {
        if ($emailClient !== null) {
            $to = $emailClient;
            $from = 'no-reply@catalogue.sofridis.com';
            $cc = ["jean-yves.berlet@groupesafo.com", "thibault.degommier@groupesafo.com", "cedric.henry@groupesafo.com ", "sekou.koita@groupesafo.com", "ridha.bensaber@groupesafo.com"];
            $object = sprintf('%s%s', '[Commande.com] - Notification de pré commande N° ', $orderNumber);

            $target_path_xlsx = $this->webRoot . '/uploads/xlsx/attachment/';
            $target_path_pdf =  $this->webRoot . '/uploads/pdf/attachment/';

            $filename_xlsx = sprintf('BDC-%s.xlsx', $orderNumber);
            $filename_pdf = sprintf('BDC-%s.pdf', $orderNumber);

            $message = \Swift_Message::newInstance()
                ->setSubject($object)
                ->setFrom($from)
                ->setTo($to)
                ->setCc($cc)
                ->setBody(
                    $this->templateEngine->render(
                        'SogedialIntegrationBundle:Email:client-email-template.html.twig',
                        array(
                            'orderNumber' => $orderNumber,
                            'totalAmount' => $totalAmount,
                            'clientName' => $clientName,
                            'comment' => $comment
                        )
                    ),
                    'text/html'
                );

            if (file_exists(sprintf('%s%s', $target_path_xlsx, $filename_xlsx)) == TRUE) {
                $message->attach(\Swift_Attachment::fromPath(sprintf('%s%s', $target_path_xlsx, $filename_xlsx)));
            }

            if (file_exists(sprintf('%s%s', $target_path_pdf, $filename_pdf)) == TRUE) {
                $message->attach(\Swift_Attachment::fromPath(sprintf('%s%s', $target_path_pdf, $filename_pdf)));
            }

            if ($this->mailer->send($message)) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * @param $orderProductsList
     * @return mixed
     */
    public function getCatalogueProductTree($orderProductsList)
    {
        $keys = ['ry', 'sf'];

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
     * @return array
     */
    public function getListMarqueMDD()
    {
        return $marqueMDD = array('GRAND JURY',
            'GRAND JURY EQUILIBRE',
            'GRAND JURY PARFUMERIE',
            'GRAND JURY BIO',
            'GRAND JURY PREMIUM',
            'CARREFOUR',
            'CARREFOUR BIO',
            'CARREFOUR KIDS',
            'CARREFOUR SELECTION',
            'CARREFOUR LIGHT',
            'CARREFOUR AGIR',
            'CARREFOUR DISCOUNT',
            'CARREFOUR EXOTIQUE',
            'CARREFOUR BABY',
            'CARREFOUR HOME',
            'CARREFOUR ECOPLA',
            'CARREFOUR CDM',
            'CARREFOUR DISCOUNT',
            'CARREFOUR HALAL',
            'CARREFOUR BON APPETIT',
            'CARREFOUR DISNEY',
            'CARREFOUR SHIELD',
            'RECETTE CARREFOUR',
            'GRAND JURY PARFUMERIE',
            'EN CUISINE',
            'MDD',
            'REFLETS DE France',
            'SAXO',
            'TERRE Italie',
            'HAPPY NUT',
            'MAITRES GOUSTIERS',
            'AUGUSTIN F',
            'Marque Distributeur',
            'COURANCES',
            'DURENMEY',
            'DURENMEYER',
            'ENGHIEN',
            'FLEURY',
            'HONORE',
            'J.LAFONT',
            'KIEFFER',
            'LARMIGNY',
            'LOUIS DE RETZ',
            'PETER HERRES GMBH',
            'BAYANIS',
            'FORTENI',
            'WESTPORT',
            'PÈRE DAMIEN',
            'PROMOCASH',
            'SAINT MERAC',
            'ESTRIBOS',
            'KEN LOUGH',
            'LOCH CASTLE',
            'OLD THAMES',
            'SNIEZKA',
            'VIKOROFF',
            'LES COSMETIQUES',
            'LES COSMETIQUES NECTAR',
            'PRESERVEX',
            'TEX',
            'TEX BABY',
            'BLUESKY',
            'GRAND JURY TOUT PRÊT',
            'EQC',
            'FQC',
            'ORIGINE & QUALITE',
            'SPNP',
            'DESTINATION SAVEURS'
        );
    }

    /**
     * @param $codeEnseigne
     * @return array
     */
    public function getMargeFromCodeEnseigne($codeEnseigne)
    {
        $taux = array();
        switch ($codeEnseigne) {
            case "4-T1":
                $taux = array(1.19, 1.19, 1.14);
                break;
            case "4-T2":
                $taux = array(1.21, 1.21, 1.14);
                break;
            case "4-T3":
                $taux = array(1.26, 1.26, 1.13);
                break;
            case "4-T4":
                $taux = array(1.19, 1.12, 1.14);
                break;
            case "4-T5":
                $taux = array(1.10, 1.10, 1.07);
                break;
            case "4-T6":
                $taux = array(1.12, 1.12, 1.14);
                break;
            case "4-T7":
                $taux = array(1.14, 1.14, 1.14);
                break;
            case "4-T8":
                $taux = array(1.10, 1.10, 1.07);
                break;
        }

        return $taux;
    }

    public function sendStockEngagementDemandeEmails($clientsPromotions, $codeEntreprise)
    {
        $mailParams = $this->getMailParams('STOCK_ENGAGEMENT', $codeEntreprise);

        if ($mailParams !== null) {
            $from = $mailParams->getFrom();
            $to = $mailParams->getTo();
            $cc = $mailParams->getMailCc();

            foreach ($clientsPromotions as $client => $moreStockRequests) {

                if (count($moreStockRequests) === 0) {
                    continue;
                }

                $message = \Swift_Message::newInstance()
                    ->setSubject($mailParams->getObject())
                    ->setFrom($from)
                    ->setTo($to)
                    ->setCc($cc)
                    ->setBody(
                        $this->templateEngine->render(
                            $mailParams->getTemplate(),
                            array(
                                "client" => current($moreStockRequests)->getClient(),
                                "moreStockRequests" => $moreStockRequests
                            )
                        ),
                        'text/html'
                    );

                $this->mailer->send($message);
            }
        }
    }

    public function sendDemandeRerencementMetiEmails($productsUnreferencedByClient)
    {
        if (sizeof($productsUnreferencedByClient) <= 0) {
            return null;
        }
        $clientCommande = current($productsUnreferencedByClient)["client_commande"];

        $client = $this->em->getRepository('SogedialSiteBundle:Client')->findOneBy(array('code' => $clientCommande));

        $entreprise = $this->em->getRepository('SogedialSiteBundle:Entreprise')->findOneBy(array('code' => $client->getEntreprise()->getCode()));

        $clientMeti = $this->em->getRepository('SogedialSiteBundle:ClientMeti')->findOneBy(array('clientAs400' => current($productsUnreferencedByClient)["client_as400"], 'region' => $entreprise->getRegion()));


        $mailParams = $this->getMailParams('REFERENCEMENT_METI', $entreprise->getCode());

        if ($mailParams !== null) {
            $from = $mailParams->getFrom();
            $to = $mailParams->getTo();

            if ($clientMeti->getMailReferencement() !== null && trim($clientMeti->getMailReferencement()) !== "") {
                $to = $clientMeti->getMailReferencement();
            }

            $cc = $mailParams->getMailCc();

            $message = \Swift_Message::newInstance()
                ->setSubject($mailParams->getObject())
                ->setFrom($from)
                ->setTo($to)
                ->setCc($cc)
                ->setBody(
                    $this->templateEngine->render(
                        $mailParams->getTemplate(),
                        array(
                            "libelleEnseigne" => $clientMeti->getLibelleSite(),
                            "produitsPrecoToRef" => $productsUnreferencedByClient
                        )
                    ),
                    'text/html'
                );

            $this->mailer->send($message);
        }
    }

    public function getMailParams($type, $codeEntreprise)
    {

        $mailParams = $this->em->getRepository('SogedialSiteBundle:MailParams')->findOneBy(array('type' => $type, 'entreprise' => $codeEntreprise));
        return $mailParams;
    }

    /**
     * @param $orderList
     * @return JsonResponse
     */
    public function generateExcelForPcmdOrders($orderList)
    {
        $today = new \DateTime('now');
        $todayStr = $today->format('Ymd');
        $startDate = date('Ymd', strtotime("-30 day"));
        $emailStartDate = date('Y-m-d', strtotime("-30 day"));

        $filePath = sprintf('%s%s%s-%s.%s',
            $this->webRoot,
            '/precommand/files/PreCommande-Sofriber-',
            $startDate,
            $todayStr,
            'csv'
        );

        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath) . '/', 0777, TRUE);
        }

        $headers = array('nom', 'ville', 'numero', 'date_validation', 'code_produit', 'denomination_produit_base', 'QuantiteColis', 'quantiteUnitaire', 'prix_unitaire', 'prix_total', 'temperature_produit');

        $result = $this->mssafe_csv($filePath, $orderList, $headers);

        if ($result) {
            return $this->sendPcmdOrdersEmail($filePath, $emailStartDate);
        } else {
            return new JsonResponse(
                array(
                    'message' => 'false'
                )
            );
        }

    }

    /**
     * @param $filePath
     * @param $startedDate
     * @return JsonResponse
     */
    public function sendPcmdOrdersEmail($filePath, $startedDate)
    {
        $to = array('jean-yves.berlet@groupesafo.com', 'cedric.henry@groupesafo.com', 'romuld.jerpan@groupesafo.com');
        $arrayCc = array('ridha.bensaber@groupesafo.com', 'sekou.koita@groupesafo.com');
        $subject = sprintf('%s%s', '[Commande.com] - Précommandes depuis le ', $startedDate);

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom('no-reply@catalogue.sofridis.com')
            ->setTo($to)
            ->setCc($arrayCc)
            ->setBody(
                $this->templateEngine->render(
                    'SogedialIntegrationBundle:Email:pcmd-orders-list-email-template.html.twig',
                    array('startedDate' => $startedDate)
                ),
                'text/html'
            );

        $message->attach(\Swift_Attachment::fromPath($filePath));

        if ($this->mailer->send($message)) {
            return new JsonResponse(
                array(
                    'message' => 'true'
                )
            );
        } else {
            return new JsonResponse(
                array(
                    'message' => 'false'
                )
            );
        }

    }

    /**
     * @param $filepath
     * @param $data
     * @param array $header
     * @return bool
     */
    protected function mssafe_csv($filepath, $data, $header = array())
    {
        if ($fp = fopen($filepath, 'w')) {
            $show_header = true;
            if (empty($header)) {
                $show_header = false;
                reset($data);
                $line = current($data);
                if (!empty($line)) {
                    reset($line);
                    $first = current($line);
                    if (substr($first, 0, 2) == 'ID' && !preg_match('/["\\s,]/', $first)) {
                        array_shift($data);
                        array_shift($line);
                        if (empty($line)) {
                            fwrite($fp, "\"{$first}\"\r\n");
                        } else {
                            fwrite($fp, "\"{$first}\",");
                            fputcsv($fp, $line, ";");
                            fseek($fp, -1, SEEK_CUR);
                            fwrite($fp, "\r\n");
                        }
                    }
                }
            } else {
                reset($header);
                $first = current($header);
                if (substr($first, 0, 2) == 'ID' && !preg_match('/["\\s,]/', $first)) {
                    array_shift($header);
                    if (empty($header)) {
                        $show_header = false;
                        fwrite($fp, "\"{$first}\"\r\n");
                    } else {
                        fwrite($fp, "\"{$first}\",");
                    }
                }
            }
            if ($show_header) {
                fputcsv($fp, $header, ";");
                fseek($fp, -1, SEEK_CUR);
                fwrite($fp, "\r\n");
            }
            foreach ($data as $line) {
                fputcsv($fp, $line, ";");
                fseek($fp, -1, SEEK_CUR);
                fwrite($fp, "\r\n");
            }
            fclose($fp);
        } else {
            return false;
        }
        return true;
    }

    /**
     * @param $orderList
     */
    public function checkPcmdOrdersFiles($orderList)
    {
        $arrayToSend = array();

        foreach ($orderList as $order) {
            $today = new \DateTime('now');
            $todayStr = $today->format('Ymd');
            $startDate = date('Ymd', strtotime("-7 day"));

            $preCommandRegionDirectory = sprintf('/%s/%s/%s', 'precommand', sprintf('%s%s', 'region', substr($order['codeEntreprise'], 0, 1)), 'CD_WEB.C');
            $filePath = $this->webRoot . $preCommandRegionDirectory . substr($order['codeEntreprise'], 0, 3) . substr($order['numero'], -6);

            if (!file_exists($filePath)) {
                $arrayToSend[] = $order['numero'];
            }
        }

        if (count($arrayToSend) > 0) {
            $this->sendPcmdOrdersFileCheckEmail($arrayToSend);
        }
    }

    /**
     * @param $listToSend
     * @return JsonResponse
     */
    public function sendPcmdOrdersFileCheckEmail($listToSend)
    {
        $to = array('ridha.bensaber@groupesafo.com', 'sekou.koita@groupesafo.com');
        $subject = sprintf('%s', '[Commande.com] - PCMD contrôle des fichiers As400');

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom('no-reply@catalogue.sofridis.com')
            ->setTo($to)
            ->setBody(
                $this->templateEngine->render(
                    'SogedialIntegrationBundle:Email:pcmd-orders-files-check-list-email-template.html.twig',
                    array('listToSend' => $listToSend)
                ),
                'text/html'
            );

        if ($this->mailer->send($message)) {
            return new JsonResponse(
                array(
                    'message' => 'true'
                )
            );
        } else {
            return new JsonResponse(
                array(
                    'message' => 'false'
                )
            );
        }

    }

    /**
     * @param $productArray
     * @return bool
     */
    private function generateBareCode($productArray)
    {
        $pathToFont = sprintf('%s/%s', $this->webRoot, 'bundles/sogedialintegration/fonts/FreeSansBold.ttf');

        foreach ($productArray as $productLine) {
            $codeEan13 = $this->fullfilWithZeroThirteen($productLine['ean13']);
            $barcode = new Barcode($codeEan13, 4, $pathToFont);
            imagepng($barcode->image(), sprintf('%s/%s%s.%s', $this->webRoot, "uploads/pdf/attachment/", $productLine['ean13'], 'png'));
        }
        sleep(1);

        return true;
    }

    /**
     * @param $productArray
     * @return array
     */
    private function calculateTva($productArray)
    {
        $tva_21_float = floatval(2.1);
        $tva_85_float = floatval(8.5);

        $totalTva_85 = 0;
        $totalTva_21 = 0;
        $totalResult = array();

        foreach ($productArray as $productLine) {
            switch ($productLine['tva']) {
                case 2.1:
                    $totalTva_21 += ($productLine['totalPrice'] * ($tva_21_float / 100));
                    break;
                case 8.5:
                    $totalTva_85 += ($productLine['totalPrice'] * ($tva_85_float / 100));
                    break;
            }
        }
        $totalResult['totalTva_21'] = $totalTva_21;
        $totalResult['totalTva_85'] = $totalTva_85;
        $totalResult['totalTva'] = ($totalTva_21 + $totalTva_85);

        return $totalResult;
    }

    /**
     * @param $value
     * @return string
     */
    protected function fullfilWithZeroThirteen($value)
    {
        $minimumStrX = 13;

        if (strlen($value) < $minimumStrX) {
            $value = str_pad($value, $minimumStrX, '0', STR_PAD_LEFT);
        }
        return $value;
    }

    /**
     * @param $order
     * @param $client
     * @return bool
     */
    public function generateXlsxForOrder($order, $client)
    {
        $path = $this->webRoot . '/uploads/xlsx/attachment/';

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $multiplyByPcb = !($this->ms->hasFeature('vente-par-unite'));
        $stockColis = $multiplyByPcb;
        $orderProducts = $this->em->getRepository('SogedialSiteBundle:Produit')->getRecapByOrderForOrderDetails($order->getId(), $order->getTemperatureCommande(), $multiplyByPcb, $stockColis);
        $products['sumColis'] = 0;
        $total = (int)count($orderProducts['result']);

        for ($i = 0; $i < $total; $i++) {
            $products['sumColis'] += $orderProducts['result'][$i]['quantite'];
        }

        $phpExcelObject = $this->phpexcel->createPHPExcelObject(sprintf('%s/%s', $this->webRoot, 'excel/Template_BdC_Excel.xlsx'));

        $sheets['dry'] = $phpExcelObject->getSheet(0);
        $productRowstyle = $sheets['dry']->getStyle('A9:G9');
        $sheetInfos['dry']['numberTotalProduct'] = 0;
        $sheetToUse = $sheets['dry'];

        $styleMarqueArray = array(
            'alignment' => array(
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            )
        );

        // TODO : information annex sur la commande
        $sheetToUse->setCellValue('B1', ucfirst($client->getNom()));
        $sheetToUse->setCellValue('B2', strtoupper($order->getEntreprise()->getNomEnvironnement()));
        $sheetToUse->setCellValue('B3', date('d-m-Y', $order->getDeliveryDate()->getTimestamp()));

        $sheetToUse->setCellValue('B4', $order->getNumero());
        $sheetToUse->setCellValue('B6', $products['sumColis']);

        for ($i = 0; $i < $total; $i++) {
            $splitCodeProduit = preg_split('/-/', $orderProducts['result'][$i]['code']);
            $trueCodeProduit = count($splitCodeProduit) >= 2 ? $splitCodeProduit[1] : $orderProducts['result'][$i]['code'];

            $currentRow = $sheetInfos['dry']['numberTotalProduct']++;
            $sheetToUse->setCellValue('A' . (9 + $currentRow), $orderProducts['result'][$i]['ean13'])->getStyle('A' . (9 + $currentRow))->applyFromArray($styleMarqueArray);
            $sheetToUse->setCellValue('A' . (9 + $currentRow), $orderProducts['result'][$i]['ean13'])->getStyle('A' . (9 + $currentRow))->getNumberFormat()->setFormatCode('0000000000000');;

            $sheetToUse->setCellValue('B' . (9 + $currentRow), $trueCodeProduit)->getStyle('B' . (9 + $currentRow))->applyFromArray($styleMarqueArray);
            $sheetToUse->setCellValue('C' . (9 + $currentRow), $orderProducts['result'][$i]['ry_fr']);
            $sheetToUse->setCellValue('D' . (9 + $currentRow), $orderProducts['result'][$i]['sf_fr']);
            $sheetToUse->setCellValue('E' . (9 + $currentRow), $orderProducts['result'][$i]['denominationProduitBase']);
            $sheetToUse->setCellValue('F' . (9 + $currentRow), $orderProducts['result'][$i]['pcb'])->getStyle('F' . (9 + $currentRow))->applyFromArray($styleMarqueArray);
            $sheetToUse->setCellValue('G' . (9 + $currentRow), $orderProducts['result'][$i]['quantite'])->getStyle('G' . (9 + $currentRow))->applyFromArray($styleMarqueArray);
        }

        $phpExcelObject->setActiveSheetIndex(0);

        $writer = $this->phpexcel->createWriter($phpExcelObject, 'Excel2007');
        $filename = sprintf('BDC-%s.xlsx', $order->getNumero());

        $filePath = sprintf('%s%s', $path, $filename);
        $writer->save($filePath);

        sleep(5);

        if (file_exists($filePath) == TRUE) {
            return true;
        } else {
            return false;
        }
    }
}