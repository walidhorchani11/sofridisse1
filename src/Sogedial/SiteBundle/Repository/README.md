SQL Utiles
==========

Tarifs 
--------

```sql
SELECT code_entreprise, code_tarification, COUNT( code_tarification )
FROM `tarif`
WHERE code_entreprise IS NOT NULL
GROUP BY code_entreprise, code_tarification
HAVING COUNT( code_tarification ) >0
```

Recupere tous les codes tarifications de toutes les société

Catalogue
---------


```sql
SELECT p.denomination_produit_base as text, p.code_produit as code, p.ean13_produit as ean13, m.libelle as marque, p.pre_commande as preCommande, IFNULL(cpm.moq_quantite, 0) as moq, t.prix_ht as init_price 
FROM produit p 
LEFT JOIN marque m ON m.code_marque=p.code_marque 
LEFT JOIN famille f ON f.code_famille = p.code_famille 
INNER JOIN assortiment ass ON ass.code_produit=p.code_produit 
LEFT JOIN client_produit_moq cpm ON cpm.code_produit = p.code_produit AND cpm.code_client = '2402-C90248' 
LEFT JOIN tarif t ON t.code_produit=p.code_produit AND t.code_tarification = '43' 
INNER JOIN stock s ON s.code_produit=p.code_produit 
WHERE ass.valeur='43' AND p.pre_commande = 1 AND p.marketing_code NOT LIKE 'AVION%' AND p.actif = 1 AND t.code_produit IS NOT NULL
```
Requete lancée sur le catalogue pour récuperer les produits, dépendra (principalement) de `client.code_tarification`, `assortiment_client.valeur`

Assortiments
------------

```sql
SELECT code_entreprise, valeur, COUNT( valeur )
FROM `assortiment`
WHERE code_entreprise = '240'
AND code_produit IS NOT NULL
GROUP BY code_entreprise, valeur
HAVING count( valeur ) >0
ORDER BY COUNT( valeur ) DESC 
```

Recupere le nombre d'assortiments par valeur pour une entreprise 