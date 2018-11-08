<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: FUNCTION PRICE CREATION
 */
class Version20150114214451 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('
            DROP FUNCTION IF EXISTS PRICE;
            CREATE FUNCTION PRICE(price DECIMAL(10,5))
                RETURNS DECIMAL(10,2)
                LANGUAGE SQL
                BEGIN
                RETURN (CEIL(price * 100) / 100);
            END;
        ');
    }

    public function down(Schema $schema)
    {
        $this->addSql('DROP FUNCTION IF EXISTS PRICE;');
    }
}
