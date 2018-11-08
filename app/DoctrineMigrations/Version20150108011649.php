<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150108011649 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('
            drop view if exists v_produit_famille;
            create view v_produit_famille as
            select
                p.id_produit,
                p.prix,
                f5.id_famille as level1,
                f4.id_famille as level2,
                f3.id_famille as level3,
                f2.id_famille as level4,
                f1.id_famille as level5
            from produit p
            left join famille f5 on f5.id_famille = p.fk_famille
            left join famille f4 on f4.id_famille = f5.fk_famille
            left join famille f3 on f3.id_famille = f4.fk_famille
            left join famille f2 on f2.id_famille = f3.fk_famille
            left join famille f1 on f1.id_famille = f2.fk_famille
            where p.fk_famille is not null;
        ');

    }

    public function down(Schema $schema)
    {
        $this->addSql('
            drop view if exists v_produit_famille;
        ');

    }
}
