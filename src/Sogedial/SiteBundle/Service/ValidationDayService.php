<?php

namespace Sogedial\SiteBundle\Service;

use \Datetime;
use Doctrine\ORM\EntityManager;

class ValidationDayService
{
    // Setup validation day and time.
    private $validationDay = 'Monday';
    private $validationHour = 18;
    private $validationMinute = 0;


    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * ValidationDayService constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * Returns the next validation day (closest monday at 14h00, local server time).
     * @return DateTime
     */
    public function getNextValidationDate($entreprise)
    {
        // Retrieve the current datetime.
        $currentDateTime = new DateTime('NOW');
        $currentDateTime = $this->convertDateToAmericaTimeZone($currentDateTime);
        $frequency = $this->getFrequency($entreprise->getCode());

        if ($entreprise->getCode() === '2222') {
            $nextValidationDeadline = $frequency->getDateFinCommande();
        } else {
            // Retrieve next monday's date and set it to 14:00.
            // Make this work as well if the next validation day is the same as the current day (in that case, if the limit hour is not yet reached, the validation day will be the same day, just a tad later).
            $nextValidationDeadline = clone $currentDateTime;
            $nextValidationDeadline->setTime($this->validationHour, $this->validationMinute);
            if (($currentDateTime->format('l') != $this->validationDay) || ($currentDateTime > $nextValidationDeadline)) {
                $nextValidationDeadline->modify($frequency)->setTime($this->validationHour, $this->validationMinute);
            }
        }

        return $nextValidationDeadline;
    }

    /**
     * @param $entreprise
     * @return Datetime
     */
    public function getNextDeliveryDate($entreprise)
    {
        $date = new \DateTime();
        $date = $this->convertDateToAmericaTimeZone($date);
        $deliveryDate = new \DateTime();
        $deliveryDate = $this->convertDateToAmericaTimeZone($deliveryDate);
        $deliveryDate->setTimestamp($date->getTimestamp());
        $indexSummerDay = intval($date->format("N"));

        $dateBateau = $this->getFrequency($entreprise->getCode());

        if ($entreprise->getTypePreCommande() == 1) {
            $deliveryDate->add(new \Dateinterval('P' . (8 - $indexSummerDay + 7) . 'D'));
        } elseif ($entreprise->getTypePreCommande() == 2 && $entreprise->getCode() === '1102') {
            $logiguaDeliveryDate = date('Y-m-d', strtotime('+ 44 days', strtotime($dateBateau)));
            $deliveryDate = new \DateTime($logiguaDeliveryDate);
        } elseif ($entreprise->getTypePreCommande() == 2 && $entreprise->getCode() === '1202') {
            $cadiDeliveryDate = date('Y-m-d', strtotime('+ 44 days', strtotime($dateBateau)));
            $deliveryDate = new \DateTime($cadiDeliveryDate);
        } elseif ($entreprise->getTypePreCommande() == 2 && $entreprise->getCode() === '1302') {
            $sebDeliveryDate = date('Y-m-d', strtotime('+ 37 days', strtotime($dateBateau)));
            $deliveryDate = new \DateTime($sebDeliveryDate);
        } elseif ($entreprise->getTypePreCommande() == 2 && $entreprise->getCode() === '2222') {
            $deliveryDate = $dateBateau->getDateLivraison();
        }

        return $deliveryDate;
    }

    /**
     * Returns the time remaining until the next basket validation (validated ECC side, only done on mondays at 14h00, local server time).
     * @return DateInterval
     */
    public function getRemainingTimeToNextValidation($entreprise)
    {
        // Retrieve the current datetime.
        $currentDateTime = new DateTime('NOW');
        $currentDateTime = $this->convertDateToAmericaTimeZone($currentDateTime);

        // Retrieve next validation day.
        $nextValidationDeadline = $this->getNextValidationDate($entreprise);

        if($entreprise->getCode() === '2222') {
            $timeStamp = $nextValidationDeadline->getTimestamp();
            $dateFormat = date('Y-m-d h:i:s A', $timeStamp);
            $objDate = new DateTime($dateFormat, new \DateTimeZone('UTC'));
            $DateFinCommandeAmerica = $this->convertDateToAmericaTimeZone($objDate);

            $result = $DateFinCommandeAmerica->diff($currentDateTime);
        } else {
            $result = $nextValidationDeadline->diff($currentDateTime);
        }

        // Reckon remaining time to next validation based on those dates.
        return $result;
    }

    function getRemainingTimeFromNow(Datetime $upcomingDateTime)
    {
        $currentDateTime = new DateTime('NOW');
        $currentDateTime = $this->convertDateToAmericaTimeZone($currentDateTime);

        // Reckon remaining time to upcoming passed date.
        return $upcomingDateTime->diff($currentDateTime);
    }

    /**
     * @param $societeCode
     * @return mixed|string
     */
    private function getFrequency($societeCode)
    {
        $frequencyDate = null;
        switch ($societeCode) {
            case '1102':
                $frequencyDate = $this->getLogiguaFrequency();
                break;
            case '1202':
                $frequencyDate = $this->getCadiFrequency();
                break;
            case '2222':
                $frequencyDate = $this->getSofridisFrequency($societeCode);
                break;
            case '1302':
                $frequencyDate = $this->getSebFrequency();
                break;
        }

        return $frequencyDate;
    }

    /**
     * @return mixed
     */
    private function getCadiFrequency()
    {
        $arrayOfList = array(
            '2' => '2018-01-08', '6' => '2018-02-05', '10' => '2018-03-05', '14' => '2018-04-02', '18' => '2018-04-30',
            '22' => '2018-05-28', '26' => '2018-06-25', '30' => '2018-07-23', '34' => '2018-08-20', '38' => '2018-09-17',
            '42' => '2018-10-15', '46' => '2018-11-12', '50' => '2018-12-10'
        );

        $currentDateTime = new DateTime('NOW');
        $currentDateTime = $this->convertDateToAmericaTimeZone($currentDateTime);
        $todayTimestamp = $currentDateTime->getTimestamp();
        $todayIso = idate('W', $todayTimestamp);

        $monday = date('Y-m-d', strtotime('monday this week'));
        $todayDate = date('Y-m-d');

        foreach ($arrayOfList as $key => $valeur) {
            if (($key >= $todayIso) && time() <= strtotime("23:59") && (strtotime($monday) === strtotime($todayDate))) {
                $frequencyDate = $valeur;
                break;
            } elseif (($key >= $todayIso) && (time() <= strtotime("23:59")) && (strtotime($monday) < strtotime($todayDate))
                || (($key >= $todayIso) && time() > strtotime("23:59"))
            ) {
                $frequencyDate = $arrayOfList[$key + 4];
                break;
            }
        }

        return $frequencyDate;
    }

    /**
     * @return mixed
     */
    private function getLogiguaFrequency()
    {
        $arrayOfList = array(
            '2' => '2018-01-08', '5' => '2018-01-29', '8' => '2018-02-19', '11' => '2018-03-12', '14' => '2018-04-02',
            '17' => '2018-04-23', '20' => '2018-05-14', '23' => '2018-06-04', '26' => '2018-06-25', '29' => '2018-07-16',
            '32' => '2018-08-06', '35' => '2018-08-27', '38' => '2018-09-17', '41' => '2018-10-08', '44' => '2018-10-29',
            '47' => '2018-11-19', '50' => '2018-12-10', '53' => '2018-12-31'
        );

        $currentDateTime = new DateTime('NOW');
        $currentDateTime = $this->convertDateToAmericaTimeZone($currentDateTime);
        $todayTimestamp = $currentDateTime->getTimestamp();
        $todayIso = idate('W', $todayTimestamp);

        $monday = date('Y-m-d', strtotime('monday this week'));
        $todayDate = date('Y-m-d');

        foreach ($arrayOfList as $key => $valeur) {
            if (($key >= $todayIso) && time() <= strtotime("23:59:59") && (strtotime($monday) === strtotime($todayDate))) {
                $frequencyDate = $valeur;
                break;
            } elseif (($key >= $todayIso) && time() <= strtotime("23:59:59") && (strtotime($monday) < strtotime($todayDate))
                || (($key >= $todayIso) && time() > strtotime("23:59:59"))
            ) {
                $frequencyDate = $arrayOfList[$key + 3];
                break;
            }
        }

        return $frequencyDate;
    }

    /**
     * @param $societeCode
     * @return mixed
     */
    private function getSofridisFrequency($societeCode)
    {
        $currentDateTime = new DateTime('NOW');
        $currentDateTime = $this->convertDateToAmericaTimeZone($currentDateTime);
        $todayTimestamp = $currentDateTime->getTimestamp();
        $todayIso = idate('W', $todayTimestamp);
        $thursdayOfThisWeekAmerica = new DateTime(date('Y-m-d 18:00:00', strtotime('thursday this week')), new \DateTimeZone('America/Martinique'));
        $todayDateTimeAmerica = new DateTime("now", new \DateTimeZone('America/Martinique') );
        $arrayOfList = $this->em->getRepository('SogedialSiteBundle:PrecoPlanning')->getListBateauDate($societeCode);
        for($i = 0; $i < count($arrayOfList); $i++) {
            if(($arrayOfList[$i]->getDateIso() <= $todayIso) && ($thursdayOfThisWeekAmerica->getTimestamp() >= $todayDateTimeAmerica->getTimestamp())) {
                $frequencyDate = $arrayOfList[$i];
            } elseif (($arrayOfList[$i]->getDateIso() <= $todayIso) && ($thursdayOfThisWeekAmerica->getTimestamp() < $todayDateTimeAmerica->getTimestamp())) {
                $valeur = $arrayOfList[$i + 1];
                $frequencyDate = $valeur;
            } elseif ($arrayOfList[$i]->getDateIso() > $todayIso) {
                break;
            }
        }

        return $frequencyDate;
    }

    /**
     * @return mixed
     */
    private function getSebFrequency()
    {
        $arrayOfList = array(
            '22' => '2018-05-28', '23' => '2018-06-04', '24' => '2018-06-11', '25' => '2018-06-18', '26' => '2018-06-25',
            '27' => '2018-07-02', '28' => '2018-07-09', '29' => '2018-07-16', '30' => '2018-07-23', '31' => '2018-07-30',
            '32' => '2018-07-08', '33' => '2018-08-13', '34' => '2018-08-20', '35' => '2018-08-27', '36' => '2018-09-33',
            '37' => '2018-09-10', '38' => '2018-09-17', '39' => '2018-09-24', '40' => '2018-10-01', '41' => '2018-10-08',
            '42' => '2018-10-15', '43' => '2018-10-22', '44' => '2018-10-29', '45' => '2018-11-05', '46' => '2018-11-12',
            '47' => '2018-11-19', '48' => '2018-11-26', '49' => '2018-12-03', '50' => '2018-12-10', '51' => '2018-12-17',
            '52' => '2018-12-24', '53' => '2018-12-31'
        );

        $currentDateTime = new DateTime('NOW');
        $currentDateTime = $this->convertDateToAmericaTimeZone($currentDateTime);
        $todayTimestamp = $currentDateTime->getTimestamp();
        $todayIso = idate('W', $todayTimestamp);

        $monday = date('Y-m-d', strtotime('monday this week'));
        $todayDate = date('Y-m-d');

        foreach ($arrayOfList as $key => $valeur) {
            if (($key >= $todayIso) && time() <= strtotime("23:59:59") && (strtotime($monday) === strtotime($todayDate))) {
                $frequencyDate = $valeur;
                break;
            } elseif (($key >= $todayIso) && time() <= strtotime("23:59:59") && (strtotime($monday) < strtotime($todayDate))
                || (($key >= $todayIso) && time() > strtotime("23:59:59"))
            ) {
                $frequencyDate = $arrayOfList[$key + 1];
                break;
            }
        }

        return $frequencyDate;
    }

    /**
     * @param $dateObject
     * @return mixed
     */
    private function convertDateToAmericaTimeZone($dateObject)
    {
        return $dateObject->setTimezone(new \DateTimeZone('America/Martinique'));
    }

}
