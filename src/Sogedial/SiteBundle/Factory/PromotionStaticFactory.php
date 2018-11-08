<?php 

namespace Sogedial\SiteBundle\Factory;
use Sogedial\SiteBundle\Service\PromotionService;
use Sogedial\SiteBundle\Service\SimpleMySQLService;
use Sogedial\SiteBundle\Service\ProductService;
use Doctrine\ORM\EntityManager;

class PromotionStaticFactory
{
    private static $em;
    private static $sql;
    private static $ps;

    public function __construct(EntityManager $em, SimpleMySQLService $sql, ProductService $ps)
    {
        self::$em = $em;
        self::$sql = $sql;
        self::$ps = $ps;
    }

    public static function createPromotionService()
    {
        $promotionService = new PromotionService(
            self::$em,
            self::$sql,
            self::$ps
        );

        return $promotionService;
    }
}