<?php

namespace Sogedial\SiteBundle\Service;

use Sogedial\SiteBundle\Service\SimpleMySQLService;

class Recherche
{
    private $sql;
    private $ps;
    private $ui;

    public function __construct(SimpleMySQLService $sql, ProductService $ps, UserInfo $ui)
    {
        $this->sql = $sql;
        $this->ps = $ps;
        $this->ui = $ui;
    }

    // l'entrée et sortie sont en UTF-8
    function natural_explode($inpututf8)
    {
        $input=utf8_decode($inpututf8);

        // ajoute un espace après toutes les séquences de lettres et toutes les séquences qui ne sont pas des lettres

        // cette merde fonctionne avec é et è, mais ne fonctionne pas avec les caractères ó, à, ò...
        // $patterns = ['/(\d+)/', '/(\p{L}+)/u', '/(\P{L}+)/u'];

        // version ISO-8859-1
        $patterns = ['/(\d+)/', '/([[:alpha:]]+)/', '/([[:^alpha:]]+)/'];

        $replacements = ['${1} ', '${1} ', '${1} '];
        $res1=preg_replace($patterns, $replacements, $input);
        $res2=trim(preg_replace('/\s+/', ' ', $res1));              // enlever les multiples espaces + les espaces au début et à la fin

        $exploded=explode(' ', utf8_encode($res2));                 // explode traite correctement les séquences Unicode
        return $exploded;
    }

    private function preg_process($expression, $input, &$dictionary, $provenance)
    {
        // cela va extraire tous les matches de $expression
        $pma_result = preg_match_all ( $expression, $input , $matches);
        if ($pma_result !== false)
        {
            // $matches[2] correspond aux 2ème (), ainsi il n'y a pas besoin d'enlever le mot "marque" nous-mêmes
            foreach ($matches[2] as $brand_entry)
            {
                // il y a toujours besoin d'explode car un mot sans espace peut aussi être "multi-mot" (coca-cola ou marque8)
                // traiter chaque match de la même façon que la liste normale
                $exploded = $this->natural_explode($brand_entry);
                foreach ($exploded as $word)
                {
                    if ($word !== ""){
                        $rm=array('phonetique' => $word, 'provenance' => $provenance);
                        $dictionary[]=$rm;
                    }
                }
            }
        }
        return preg_replace($expression, ' ', $input);
    }

    // retourne le tableau contenant les entrées phonétiques à indexer ou à chercher
    // chaque entrée est un tableau associatif avec les clefs 'phonetique' et 'provenance'
    // provenance : 1 = libellé, 2 = marque, 3 = rayon, 4 = quantité
    public function searchExplode($toIndex)
    {
        $rms=array();

        if(strlen($toIndex) > 0 ){
            // cela va extraire tous les matches de 'marque:"SOMETHING something"'
            $wo_brands1 = $this->preg_process ( '/(^|\s+)marque\s*:\s*"([^"]*)"/u', $toIndex , $rms, 2 );

            // cela va extraire tous les matches de 'marque:SOMETHING' (pas de "")
            $wo_brands2 = $this->preg_process ( '/(^|\s+)marque\s*:\s*([^ ]+)/u', $wo_brands1 , $rms, 2 );

            $wo_rayons1 = $this->preg_process ( '/(^|\s+)rayon\s*:\s*"([^"]*)"/u', $wo_brands2 , $rms, 3 );
            $wo_rayons2 = $this->preg_process ( '/(^|\s+)rayon\s*:\s*([^ ]+)/u', $wo_rayons1 , $rms, 3 );

            $exploded = $this->natural_explode($wo_rayons2);

            foreach ($exploded as $word)
            {
                if($word !== ""){
                    $rm=array('phonetique' => $word, 'provenance' => 1);
                    $rms[]=$rm;
                }
            }
        }

        return $rms;
    }

    public function getSuggestions($query)
    {
        // RESTE A FAIRE :
        // résultats de recherche "normaux"
        // recherche par EAN-13 et EAN-8 (matching à la fin) + code produit
        // --- nice to have ---
        // "refreshing"
        // synonymes
        // erreur de frappe / phonetique
        // quantités
        // résultats structurés : marques, mots clefs, produits
        // FAIT - recherche par rayon etc.
        // résultats structurés avec avec le nombre de résultats
        // les photos
        // les véritables suggestions (les mots "liés" par correlation)

        // l'algorithme de l'indexation, des suggestions et de la recherche

        // 0. composer la demande (uniquement pour l'indexation)
        //    -> 'marque:"MARQUE" DENOMINATION'
        //    marque:"coca-cola" -> explode (marque, coca) (marque, cola)
        //    denomination : "BTE 1.5L" -> explode (mot bte) (mot 1) (mot 5) (mot L) (mot bouteille [synonyme]) (qte 1500 [g,ml])
        //    tout est indexé
        //    ainsi, lors de la recherche, cette entrée pourra matcher plus de requêtes

        // 1. traiter la demande :
        //    marque:"danone" yaourt 300gr
        //    --> (marque, danone)
        //    --> & (mot yaourt) [pas besoin de synonymes lors de la recherche]
        //    --> & (((mot 300) & (mot gr)) | (qte 1500 [g,ml])))     --> nice to have
        //
        //    lors d'une recherche, tous les termes doivent être présents.
        //
        //    les "mots" peuvent matcher les mots et les marques (tout simplement pour pouvoir taper le nom d'une marque sans devoir taper "marque:")
        //    les "marques" peuvent matcher uniquement les marques
        //    les "quantités" peuvent matcher uniquement les quantités (c'est une catégorie automatique, on ne peut pas le saisir sur la ligne de recherche)

        // voilà à quoi ressemblerait la recherche des produits:
        /*
        SELECT DISTINCT piv.code_produit, p.denomination_produit_base
        FROM produit_recherche_mot piv
        LEFT JOIN produit p ON p.code_produit=piv.code_produit
        WHERE
        piv.code_produit IN (
          SELECT piv.code_produit
          FROM recherche_mot rm
          LEFT JOIN produit_recherche_mot piv ON piv.id_recherche_mot=rm.id_recherche_mot
          WHERE rm.phonetique LIKE "%ju%"
        )
        AND
        piv.code_produit IN (
          SELECT piv.code_produit
          FROM recherche_mot rm
          LEFT JOIN produit_recherche_mot piv ON piv.id_recherche_mot=rm.id_recherche_mot
          WHERE rm.phonetique LIKE "%po%"
        )
        */
        // évidemment, on peut combiner les AND et les OR comme on veut sur les blocs "piv.code_produit IN"
        $rows = $this->getEntriesByQuery($query,0,10);
        // attention, en fait, cela peut envoyer jusqu'à 20 résultats

        $suggestions = array();
        $i = 0;
        foreach ($rows as $row)
        {
            $text=$row['text'];
            $text_to_use=$text;
            $category=$row['marque'];
            if ($category==="NON DETERMINE")
            {
                $category="";
            }
            $code_produit_complet = $row['code'];
            $code_produit = preg_split('/-/', $code_produit_complet)[1];
            $ean13 = $row['ean13'];

            // utilisez <em> dans 'text' pour signaler les parties utilisées pour la recherche
            $suggestion = array(
                    'text' => $text,
                    'text_to_use' => $text_to_use,
                    'category' => $category,
                    'code_produit' => $code_produit,
                    'ean13' => $ean13
                );
            $suggestions[]=$suggestion;
            $i++;
            if ($i == 10)
            {
                break;
            }
        }
        return $suggestions;
    }

    // appelé par les suggestions, mais aussi directement par la recherche principale
    public function getEntriesByQuery($query, $limit1=false, $limit2=false)
    {
        // TODO refaire en ne faisant qu'une requête SQL et pas deux pour tout sauf la recherche ?

        $tri = "libelle";
        $ordertri = 2;
        if (array_key_exists('tri', $query)){
            $tri = $query['tri'];
        }
        if (array_key_exists('ordertri', $query)){
            $ordertri = $query['ordertri'];
        }

        if (array_key_exists('kind', $query)){
            if(strlen($query['kind']) > 0){
                $kind = $query['kind'];
                $queryTT = $this->getEntriesKind($kind);
            }
        }
        elseif(array_key_exists('codeFamille', $query)){
            $secteur = $query['codeSecteur'];
            $rayon = $query['codeRayon'];
            $famille = $query['codeFamille'];
            $queryTT = $this->getEntriesFamille($famille, $rayon, $secteur);

            $rows = $this->extendsDataProduct($queryTT, $limit1, $limit2, $tri, $ordertri);
            return $rows;
        }
        elseif(array_key_exists('codeRayon', $query)){
            $secteur = $query['codeSecteur'];
            $rayon = $query['codeRayon'];
            $queryTT = $this->getEntriesRayon($rayon, $secteur);

            $rows = $this->extendsDataProduct($queryTT, $limit1, $limit2, $tri, $ordertri);
            return $rows;
        }
        elseif(array_key_exists('codeSecteur', $query)){
            $secteur = $query['codeSecteur'];
            $queryTT = $this->getEntriesSecteur($secteur);

            $rows = $this->extendsDataProduct($queryTT, $limit1, $limit2, $tri, $ordertri);
            return $rows;
        }
        elseif(array_key_exists('search', $query)){
            $phonetiques = $this->searchExplode($query['search']);
            $code_in = $query['search'];
            $queryTT = $this->getEntriesSearch($code_in, $phonetiques);
        }
        $rows = $this->extendsDataProduct($queryTT, $limit1, $limit2, $tri, $ordertri);
        return $rows;
    }

    /**
    * @param array $query
    */
    public function getCountEntriesByQuery(array $query){
        if(array_key_exists('search', $query)){
            return $this->getCountResults(
                $this->getEntriesSearch(
                    $query['search'],
                    $this->searchExplode(
                        $query['search']
                    )
                )
            );
        } else {
            return 0;
        }
    }

    // 1ère partie (ici) : avoir les codes produits
    // 2ème partie (en dehors de cette fonction) : récupérer les infos produits
    private function getEntriesSearch($code_in = "", $phonetiques = "")
    {
        $allProducts = "INSERT INTO tt (`code_produit`) SELECT DISTINCT p.code_produit FROM produit p";
        $query1 = "";
        $query2 = "";
        if(count($code_in) > 0 || count($phonetiques) > 0){
            if(count($code_in) > 0){
                $code=preg_replace('/\s+/', '', $code_in);  // enlever les espaces
                if (strlen($code)>=3 && strlen($code)<=18)        // EAN 13+5 est le max
                {
                    $query1 = $allProducts.
                        " WHERE (p.code_produit LIKE '%".$code."%' OR p.ean13_produit LIKE '%".$code."%')";
                }
            }

            if(count($phonetiques)> 0){
                $intersect_count = 0;
                $intersect_select = "";
                foreach ($phonetiques as $ph_element)
                {
                    $phonetique = $ph_element['phonetique'];
                    $provenance = $ph_element['provenance'];
                    if ($phonetique === "")
                    {
                        continue;
                    }
                    if ($intersect_count > 0)
                    {
                        $intersect_select.=" UNION ALL ";
                    }
                    $intersect_count++;

                    $provenance_filter="";
                    if ($provenance === 1)
                    {
                        $provenance_filter = "AND rm.provenance!=4";     // mais pas le 4 (quantité)
                    }
                    else if ($provenance === 2 || $provenance === 3 || $provenance === 4)     // marque, rayon ou quantité
                    {
                        $provenance_filter = "AND rm.provenance=".$provenance;
                    }

                    // TODO think : mettre SELECT DISTINCT déjà à ce niveau ? vérifier avec profiling !
                    $intersect_select.=" SELECT DISTINCT piv.code_produit FROM recherche_mot rm LEFT JOIN produit_recherche_mot piv on piv.id_recherche_mot=rm.id_recherche_mot".
                        " WHERE rm.phonetique LIKE '%".$phonetique."%' ".$provenance_filter." ";
                }
                if ($intersect_count > 0)
                {
                    $query2 = "
                        REPLACE INTO tt (`code_produit`)
                        SELECT final.code_produit FROM (
                        SELECT aggr.code_produit, COUNT(*) as count FROM ( ".$intersect_select.") aggr GROUP BY aggr.code_produit HAVING count=".$intersect_count.
                        " ) final";
                }
            }
        }

        // attention la base est utf8_general_ci, mais toutes les tables sont utf8_unicode_ci. Si on spécifie
        // COLLATE qui ne matche pas, on aura l'erreur Illegal mix of collations
        //TODO gerer filtres assortiment et enseigne pour optimisation a cette etape la
        if (strlen($query1) > 0 && strlen($query2) == 0){
            $queryTT = $query1;
        } elseif (strlen($query1) == 0 && strlen($query2) > 0){
            $queryTT = $query2;
        } elseif (strlen($query1) == 0 && strlen($query2) == 0){
            $queryTT = $allProducts;
        } else {
            $queryTT =  $query1.";".$query2;
        }

        return  $queryTT;
    }

    private function getEntriesKind($kind = "")
    {
        $where1 = "";

        if(count($kind) > 0){
            $kind=preg_replace('/\s+/', '', $kind);  // enlever les espaces
            if (strlen($kind)> 0 && $kind==="promotion")
            {
                // TODO
                // ce qui parait curieux :
                // doublons (par rapport au final_select_query) d'expressions sur stock, assortiment, p.actif
                // idéalement, on devrait enlever le plus de produit possible au plus tôt, il faudrait donc remonter le maximum de conditions...
                // mais il faut le faire dans TOUS les chemins (mots de recherche, EAN, familles etc.)

                // TODO utiliser getPromosQueryElements() plus, potentiellement, getEnrichQuery !!! copier-coller !

                // l'ordre de conditions de join parait curieux, aussi. Je remonterais bien les conditions sur pr. directement après JOIN pr
                $where1 = "INNER JOIN promotion pr ON CURRENT_TIMESTAMP() >= pr.date_debut_validite
                            AND CURRENT_TIMESTAMP() <= pr.date_fin_validite
                            LEFT JOIN marque m ON m.code_marque=p.code_marque
                            INNER JOIN stock st ON st.code_produit=p.code_produit
                            AND pr.code_type_promo IS NOT NULL
                            AND pr.code_type_promo != ''
                            AND pr.code_type_promo != 'TX'
                            AND pr.code_produit = p.code_produit
                            INNER JOIN assortiment ass ON ass.code_produit=p.code_produit
                            AND p.actif = 1
                            AND ass.valeur='".$this->ui->code_assortiment."'
                            AND (pr.code_client = '".$this->ui->code_client."'
                            OR pr.code_enseigne = '".$this->ui->code_enseigne."')";             // ce code est incorrect pour les promos de type RG et CA qui n'ont ni code client, ni code enseigne
            }
            elseif(strlen($kind)> 0 && $kind==="new"){
                $where1 = "WHERE (p.nature_code = 'NOUVEAUTE')";
            }
        }

        //TODO gerer filtres assortiment et enseigne pour optimisation a cette etape la
        $queryTT = "INSERT INTO tt (`code_produit`)
            SELECT DISTINCT p.code_produit
            FROM produit p ".$where1;
        return  $queryTT;
    }

    private function getEntriesSecteur($secteur = "")
    {
        $where1 = "";

        if(count($secteur) > 0){
            $rayon=preg_replace('/\s+/', '', $secteur);  // enlever les espaces
            if(strlen($secteur)> 0){
                $where1 = "WHERE (p.code_secteur = '".$secteur."')";
            }
        }

        //TODO gerer filtres assortiment et enseigne pour optimisation a cette etape la
        $queryTT = "INSERT INTO tt (`code_produit`)
            SELECT DISTINCT p.code_produit
            FROM produit p ".$where1;
        return  $queryTT;
    }

    private function getEntriesRayon($rayon = "", $secteur = "")
    {
        $where1 = "";

        if(count($rayon) > 0){
            $rayon=preg_replace('/\s+/', '', $rayon);  // enlever les espaces
            if(strlen($rayon)> 0){
                $where1 = "WHERE (p.code_rayon = '".$rayon."' AND p.code_secteur = '".$secteur."' )";
            }
        }

        //TODO gerer filtres assortiment et enseigne pour optimisation a cette etape la
        $queryTT = "INSERT INTO tt (`code_produit`)
            SELECT DISTINCT p.code_produit
            FROM produit p ".$where1;
        return  $queryTT;
    }

    private function getEntriesFamille($famille = "", $rayon = "", $secteur = "")
    {
        $where1 = "";

        if(count($famille) > 0){
            $famille=preg_replace('/\s+/', '', $famille);  // enlever les espaces
            if(strlen($famille)> 0){
                $where1 = "WHERE (p.code_famille = '".$famille."' AND p.code_rayon = '".$rayon."'  AND p.code_secteur = '".$secteur."' )";
            }
        }

        //TODO gerer filtres assortiment et enseigne pour optimisation a cette etape la
        $queryTT = "INSERT INTO tt (`code_produit`)
            SELECT DISTINCT p.code_produit
            FROM produit p ".$where1;
        return  $queryTT;
    }

    private function getCountResults($queryTT){
        $create_temp_table_query = "CREATE TEMPORARY TABLE tt (
            `code_produit` varchar(11) NOT NULL,
            PRIMARY KEY(code_produit)
            ) COLLATE 'utf8_unicode_ci'";
        $final_select_query = $this->ps->getCountEnrichQuery($this->ui->code_enseigne, $this->ui->code_tarification, $this->ui->code_assortiment,
            "tt INNER JOIN produit p ON p.code_produit=tt.code_produit", "", "");
        $all_queries = $create_temp_table_query.";".$queryTT.";".$final_select_query;
        $resultat_produits = $this->sql->multi_query($all_queries);
        return intval($resultat_produits[0]["search_count"]);
    }

    private function extendsDataProduct ($queryTT, $limit1, $limit2, $tri, $ordertri)
    {
        switch($tri){
            case 'libelle': $tri = "p.denomination_produit_base"; break;
            case 'marque': $tri = "m.libelle"; break;
            case 'prixht': $tri = "t.prix_ht"; break;
            default: $tri = "p.denomination_produit_base"; break;
        }

        switch($ordertri){
            case 1: $ordertri = 'DESC'; break;
            case 2: $ordertri = 'ASC'; break;
            default: $ordertri = 'ASC'; break;
        }

        $create_temp_table_query = "CREATE TEMPORARY TABLE tt (
            `code_produit` varchar(11) NOT NULL,
            PRIMARY KEY(code_produit)
            ) COLLATE 'utf8_unicode_ci'";

        // remarque : dans les requêtes legacy (Doctrine) on utilise LEFT JOIN pour tarifs, i.e. le produit sera affiché même si il n'a pas de tarif. (TBC)
        // réponse : non, en réalité, la condition sur t.code_enseigne faisait que de toute façon cette entrée serait droppée.

        $final_select_query = $this->ps->getEnrichQuery($this->ui->code_enseigne, $this->ui->code_tarification, $this->ui->code_assortiment,
            "tt INNER JOIN produit p ON p.code_produit=tt.code_produit", "", "");
        $final_select_query .=  " ORDER BY  f.libelle ASC, ".$tri." ".$ordertri." ".
            (($limit1 === false) ? "" : (" LIMIT ".$limit1.",".$limit2));

        $all_queries = $create_temp_table_query.";".$queryTT.";".$final_select_query;
        $resultat_produits = $this->sql->multi_query($all_queries);

        return $resultat_produits;
    }
}