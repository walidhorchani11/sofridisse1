<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150209202909 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('
            DROP PROCEDURE IF EXISTS COEF_CALCULATOR;
            CREATE PROCEDURE COEF_CALCULATOR()
            BEGIN
                DECLARE nb_famille INT DEFAULT 0;
                DECLARE i INT DEFAULT 0;
                create table if not exists produit_coef_tmp like produit_coef;
                select count(*) from user_family_selections into nb_famille;
                set i=0;
                WHILE i<nb_famille DO 
                    select user_id, entity, coefficient, is_new, show_price, show_exclusivity, show_promotion
                    INTO @user, @id_famille, @coef, @is_new, @show_price, @show_exclusivity, @show_promotion
                    from user_family_selections
                    limit i,1;

                    insert into produit_coef_tmp(id_produit, user_id, total_price, is_new, show_price, show_exclusivity, show_promotion) 
                    select
                        id_produit,
                        @user,
                        PRICE(prix * @coef),
                        @is_new,
                        @show_price,
                        @show_exclusivity,
                        @show_promotion
                    from v_produit_famille
                    where (level1 = @id_famille
                    or level2 = @id_famille
                    or level3 = @id_famille
                    or level4 = @id_famille
                    or level5 = @id_famille)
                    and id_produit not in (select entity from user_product_selections where user_id = @user)
                    on duplicate key update total_price = PRICE(prix * @coef);

                    SET i = i + 1;
                END WHILE;

                insert into produit_coef_tmp(id_produit, user_id, total_price, is_new, show_price, show_exclusivity, show_promotion) 
                select
                    p.id_produit,
                    up.user_id,
                    PRICE(p.prix * up.coefficient),
                    up.is_new,
                    up.show_price,
                    up.show_exclusivity,
                    up.show_promotion
                from user_product_selections up
                left join produit p on p.id_produit = up.entity
                on duplicate key update total_price = PRICE(p.prix * up.coefficient);

                insert into produit_coef(id_produit, user_id, total_price, is_new, show_price, show_exclusivity, show_promotion) 
                select 
                    tmp.id_produit,
                    tmp.user_id,
                    tmp.total_price,
                    tmp.is_new,
                    tmp.show_price,
                    tmp.show_exclusivity,
                    tmp.show_promotion
                from produit_coef_tmp tmp
                where 
                    not exists (
                        select 1
                        from produit_coef pc
                        where pc.id_produit = tmp.id_produit
                        and pc.user_id = tmp.user_id
                    );

                update produit_coef pc
                inner join produit_coef_tmp tmp
                    on pc.user_id = tmp.user_id
                    and pc.id_produit = tmp.id_produit
                    and
                    (
                        pc.is_new <> tmp.is_new
                        or pc.show_promotion <> tmp.show_promotion
                        or pc.show_price <> tmp.show_price
                        or pc.show_exclusivity <> tmp.show_exclusivity
                        or pc.total_price <> tmp.total_price
                    )
                set pc.is_new = tmp.is_new,
                    pc.show_promotion = tmp.show_promotion,
                    pc.show_price = tmp.show_price,
                    pc.show_exclusivity = tmp.show_exclusivity,
                    pc.total_price = tmp.total_price;

                delete from pc using produit_coef as pc
                where
                    not exists (
                        select 1
                        from produit_coef_tmp tmp
                        where pc.id_produit = tmp.id_produit
                        and pc.user_id = tmp.user_id
                    );

                drop table produit_coef_tmp;
            END;
        ');


    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
