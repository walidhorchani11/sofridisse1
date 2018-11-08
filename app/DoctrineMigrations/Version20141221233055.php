<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141221233055 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("
                CREATE EVENT disable_produit_activity
                ON SCHEDULE EVERY 1 DAY STARTS '2014-12-21 00:00:01'
                COMMENT 'Désactive les produits dont la période d\'activité n\'est pas en cours'
                DO
                    update produit set actif = 0 where actif = 1 AND date_fin_activite <= NOW();
        ");

        $this->addSql("
                CREATE EVENT enable_produit_activity
                ON SCHEDULE EVERY 1 DAY STARTS '2014-12-21 00:00:01'
                COMMENT 'Active les produits dont la période d\'activité est en cours'
                DO
                    update produit set actif = 1 where actif = 0 AND date_debut_activite <= NOW() AND date_fin_activite > NOW();
        ");

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP EVENT IF EXISTS disable_produit_activity');
        $this->addSql('DROP EVENT IF EXISTS enable_produit_activity');

    }
}
