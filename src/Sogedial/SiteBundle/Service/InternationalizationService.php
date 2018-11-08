<?php

namespace Sogedial\SiteBundle\Service;

class InternationalizationService extends AbstractService
{
    public function __construct()
    {
    }

    /**
    * Return a date time formatted in french
    * @paral string format
    * @param DateTime date
    * @return string
    */
    public function frenchDate($format, \DateTime $date)
    {
        $words = explode(" ", $format);
        $frenchDateResult = "";
        foreach($words as $word){
            $result = "";
            switch($word){
                case 'dd':
                    $result = $date->format("d");
                break;
                case 'd':
                    $indexDayWeek = intval($date->format("N"));
                    switch($indexDayWeek){
                        case 1:
                            $result = 'Lundi';
                            break;
                        case 2:
                            $result = 'Mardi';
                            break;
                        case 3:
                            $result = 'Mercredi';
                            break;
                        case 4:
                            $result = 'Jeudi';
                            break;
                        case 5:
                            $result = 'Vendredi';
                        case 6:
                            $result = 'Samedi';
                            break;
                        case 7:
                            $result = 'Dimanche';
                            break;
                    }
                    break;
                case 'm':
                    $indexMonth = intval($date->format('m'));
                    switch($indexMonth){
                        case 1:
                            $result = 'Janvier';
                            break;
                        case 2:
                            $result = 'Février';
                            break;
                        case 3:
                            $result = 'Mars';
                            break;
                        case 4:
                            $result = 'Avril';
                            break;
                        case 5:
                            $result = 'Mai';
                            break;
                        case 6:
                            $result = 'Juin';
                            break;
                        case 7:
                            $result = 'Juillet';
                            break;
                        case 8:
                            $result = 'Août';
                            break;
                        case 9:
                            $result = 'Septembre';
                            break;
                        case 10:
                            $result = 'Octobre';
                            break;
                        case 11:
                            $result = 'Novembre';
                            break;
                        case 12:
                            $result = 'Décembre';
                            break;
                    }
                    break;
                case 'Y':
                    $result = $date->format("Y");
                    break;

            }
            $frenchDateResult .= " " . $result;
        }
        return $frenchDateResult;
    }
}
