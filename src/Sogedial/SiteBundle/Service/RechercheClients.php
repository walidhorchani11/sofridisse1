<?php

namespace Sogedial\SiteBundle\Service;

use Sogedial\SiteBundle\Service\SimpleMySQLService;

class RechercheClients
{
    private $sql;
    private $cs;
    private $ui;

    public function __construct(SimpleMySQLService $sql, ClientService $cs, UserInfo $ui)
    {
        $this->sql = $sql;
        $this->cs = $cs;
        $this->ui = $ui;
        $this->code_entreprise = false;
    }

    public function setCodeEntreprise($code_entreprise){
        $this->code_entreprise = $code_entreprise;        
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
        $pma_result = preg_match_all ( $expression, $input , $matches  );
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
      
        $em = $this->cs->getEm();
        $rows = $em->getRepository('SogedialSiteBundle:Client')->getListClientsFromNeedle($query["search"], $this->code_entreprise);
        // attention, en fait, cela peut envoyer jusqu'à 20 résultats

        $suggestions = array();
        $i = 0;
        foreach ($rows as $row)
        {
            $text=$row['text'];
            $text_to_use=$text;
            $category="";

            // utilisez <em> dans 'text' pour signaler les parties utilisées pour la recherche
            $suggestion = array(
                    'text' => $text,
                    'text_to_use' => $text_to_use,
                    'category' => $category
                );

            $suggestions[]=$suggestion;
            $i++;

            if ($i == 10){
                break;
            }
        }
        return $suggestions;
    }

    // appelé par les suggestions, mais aussi directement par la recherche principale
    public function getEntriesByQuery($query, $limit1=false, $limit2=false)
    {
        $em = $this->cs->getEm();
        $rows = $em->getRepository('SogedialSiteBundle:Client')->getListClientsFromNeedle($query["search"], $limit1, $limit2);
        return $rows;
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
        $final_select_query = $this->cs->getEnrichQuery($this->ui->code_enseigne, $this->ui->code_tarification, $this->ui->code_assortiment,
            "tt INNER JOIN produit p ON p.code_produit=tt.code_produit", "", "");
        $final_select_query .=  " ORDER BY  f.libelle ASC, ".$tri." ".$ordertri." ".
            (($limit1 === false) ? "" : (" LIMIT ".$limit1.",".$limit2));

        $all_queries = $create_temp_table_query.";".$queryTT.";".$final_select_query;

        $resultat_produits = $this->sql->multi_query($all_queries);
 
        return $resultat_produits;
    }
}