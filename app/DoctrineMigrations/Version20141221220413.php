<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141221220413 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE histo_date_activite (id INT AUTO_INCREMENT NOT NULL, produit_id INT DEFAULT NULL, date_debut_activite DATETIME NOT NULL, date_fin_activite DATETIME NOT NULL, date_create DATETIME NOT NULL, INDEX IDX_88897726F347EFB (produit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('ALTER TABLE histo_date_activite ADD CONSTRAINT FK_88897726F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id_produit);');

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
