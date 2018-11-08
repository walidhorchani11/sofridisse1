<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150108234926 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
                CREATE EVENT refresh_product_price_coef
                ON SCHEDULE EVERY 10 MINUTE STARTS '2015-01-01 00:00:01'
                COMMENT 'Rafraichit la table produit_coef en lancant la procedure stocké COEF_CALCULATOR'
                DO
                    call COEF_CALCULATOR();
        ");

    }

    public function down(Schema $schema)
    {
        $this->addSql('DROP EVENT IF EXISTS refresh_product_price_coef');
        // this down() migration is auto-generated, please modify it to your needs

    }
}
