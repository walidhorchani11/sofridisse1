delete FROM tarif WHERE code_tarif like '401-%' and code_region = '4';

insert into tarif(code_tarif,
 code_enseigne,
 code_tarification, 
 code_region,
 code_entreprise, 
 code_produit,
 prix_ht, 
 prix_pvc,
 created_at,	
 date_debut_validite,
 code_ean_13)

SELECT CONCAT(p.code_entreprise,"-", SUBSTRING_INDEX(SUBSTRING_INDEX(p.code_produit, '-', -1), ' ', 1),"-", SUBSTRING_INDEX(SUBSTRING_INDEX(e.code_enseigne, '-', -1), ' ', 1) ) as code_tarif,
    e.code_enseigne as code_enseigne,
    null as code_tarification,
    '4' as code_region,
    '401' as code_entreprise,
    p.code_produit as code_produit,
    p.prix_preste as prix_ht,
    0 as prix_pvc, 
    NOW() as created_at,
    NOW() as date_debut_validite,
    p.ean13_produit as code_ean_13
    FROM produit p, enseigne e 
    WHERE p.code_entreprise = '401' 
    AND e.code_enseigne LIKE '4-%'
    AND e.code_enseigne !=  '4-SE$'
    AND p.prix_preste IS NOT NULL
    AND p.prix_preste > 0;