<?php

namespace Sogedial\SiteBundle\Service;


class TimeConverter
{
    /**
     * @param $seconds
     * @return string
     */
    function sec_to_time($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor($seconds % 3600 / 60);
        $seconds = $seconds % 60;

        return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
    }
}