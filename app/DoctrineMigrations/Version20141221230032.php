<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141221230032 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('
                CREATE TRIGGER after_produit_update
                AFTER UPDATE ON produit
                FOR EACH ROW BEGIN
                    IF NEW.date_debut_activite <> OLD.date_debut_activite OR NEW.date_fin_activite <> OLD.date_fin_activite THEN
                        INSERT INTO histo_date_activite (produit_id, date_debut_activite, date_fin_activite, date_create) VALUES (NEW.id_produit, NEW.date_debut_activite, NEW.date_fin_activite, NOW()); 
                    END IF;
                END
        ');

        $this->addSql('
                CREATE TRIGGER after_produit_insert
                AFTER INSERT ON produit
                FOR EACH ROW BEGIN
                    INSERT INTO histo_date_activite (produit_id, date_debut_activite, date_fin_activite, date_create) VALUES (NEW.id_produit, NEW.date_debut_activite, NEW.date_fin_activite, NOW()); 
                END
        ');

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
