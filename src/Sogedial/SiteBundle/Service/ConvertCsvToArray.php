<?php

namespace Sogedial\SiteBundle\Service;


class ConvertCsvToArray
{
    /**
     * @param $filename
     * @param string $delimiter
     * @return array|bool
     */
    public function convert($filename, $delimiter = ',', $ignoreline1 = false)
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            return FALSE;
        }

        $header = NULL;
        $data = array();

        if (($handle = fopen($filename, 'r')) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                if($ignoreline1 === true){
                    $ignoreline1 = false;
                } else {
                    $data[] = $row;
                }
            }
            fclose($handle);
        }
        return $data;
    }
}