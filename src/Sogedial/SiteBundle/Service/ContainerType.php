<?php

namespace Sogedial\SiteBundle\Service;

class ContainerType extends AbstractService
{
    const KEY_POSITIVE_COLD = 'positive_cold';
    const KEY_NEGATIVE_COLD = 'negative_cold';
    const KEY_AMBIANT       = 'ambiant';

    /**
     * Transcode product temperature key to container type key
     *
     * @param string $code
     * @return string
     */
    public static function  transcode($code)
    { 
        $result = null;
        $values = array(
            '1positif' => self::KEY_POSITIVE_COLD,
            '2negatif' => self::KEY_NEGATIVE_COLD,
            '3ambiant' => self::KEY_AMBIANT,
        );
        if (true === array_key_exists($code, $values)) {
            $result = $values[$code];
        }
        return $result;
    }
}
