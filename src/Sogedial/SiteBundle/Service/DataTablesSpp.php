<?php
namespace Sogedial\SiteBundle\Service;

use Doctrine\ORM\EntityManager;

/**
 * The example class for server side logic
 * see https://www.datatables.net/examples/data_sources/server_side.html
 */
class DataTablesSpp
{
    protected $em;
    static $glue = "\n";

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    private function sql_exec($sql, $bindings = null)
    {
        $stmt = $this->em->getConnection()->prepare($sql);

        if (is_array($bindings)) {
            for ($i = 0, $ien = count($bindings); $i < $ien; $i++) {
                $binding = $bindings[$i];
                $stmt->bindValue($binding['key'], $binding['val'], $binding['type']);
            }
        }

        try {
            $stmt->execute();
        } catch (\PDOException $e) {
            self::fatal("An SQL error occurred: " . $e->getMessage());
        }

        return $stmt->fetchAll();
    }

    public function getProduct($temperature, $user, $request, $columns, $filter = [])
    {
        // Select actual data
        $bindings = [];
        $sql = self::selectProduct($temperature, $user, $request, $columns, $filter, $bindings);
        $data = self::sql_exec($sql, $bindings);

        // Data set length after filtering
        $resFilterLength = self::sql_exec("SELECT FOUND_ROWS() as found_rows");
        $recordsFiltered = isset($resFilterLength[0]['found_rows']) ? $resFilterLength[0]['found_rows'] : 0;

        // Total data set length
        //$resTotalLength = self::sql_exec(self::selectProductTotal($temperature, $user));
        $recordsTotal = isset($resTotalLength[0]['total']) ? $resTotalLength[0]['total'] : 0;

        $totalColumns = array_filter($columns, function ($item) {
            return !empty($item['total']);
        });

        //$total = self::data_output($totalColumns, $resTotalLength);
        //$total = reset($total);

        return [
            //'total' => $total,
            'draw' => isset($request['draw']) ? intval($request['draw']) : 1,
            'recordsTotal' => intval($recordsTotal),
            'recordsFiltered' => intval($recordsFiltered),
            'data' => self::data_output($columns, $data)
        ];
    }

    public function getProductTree($user)
    {
        return self::sql_exec(self::selectProductTree($user));
    }

    static function selectProduct($temperature, $user, $request, $columns, $filter, &$bindings)
    {
        return implode(self::$glue, [
            'SELECT SQL_CALC_FOUND_ROWS ',
            self::selectFields(),
            'FROM produit',

            self::joinParents(),
            //self::joinRightsByFamilySelectins($user),

            self::joinPhoto(),
            //self::joinColis(),
            //self::joinCommand(),
            self::joinMarque(),

            'WHERE actif = 1',
            'AND temperature = \'' . $temperature . '\'',

            '/*datatables params filter*/',
            //self::filter($request, $columns, $bindings),
            //self::filterFamily($filter),

           // 'GROUP BY produit.code_produit',
            self::order($request, $columns),
            self::limit($request, $columns),
        ]);
    }

    /*
    *  Client-Side for calculated fields file jquery.catalogue.js::calculRow() 
    */
    static function selectFields()
    {
        return implode(', ', [
            //'code_produit',
            'source_photo',
            //'code_marque',
//            'prix',
            'denomination_produit_base',
            'ean13_produit',
            'dlc_moyenne',
//            'pcb_produit',
 //           "REPLACE(poidsBrutUVC,',','.') AS poidsBrutUVC",
   //         "REPLACE(volumeColis,',','.') AS volumeColis",
     //       'quantity',
//            'ROUND(prix_revient_reference * pcb_produit * quantity, 2) AS valeur',
//            'ROUND(pcb_produit * quantity, 2) AS uvc',
//            "ROUND(REPLACE(poidsBrutUVC,',','.') * pcb_produit * quantity, 2) AS poids",
       //     "ROUND(REPLACE(volumeColis,',','.') * quantity, 2) AS cubage",
        ]);
    }

    /*
    *  Client-Side for calculated fields file jquery.catalogue.js::calculFooter() 
    */
    static function selectProductTree($user)
    {
        return implode(self::$glue, [
            'SELECT COUNT(produit.code_produit) as count',
            '/*nesting level 3*/',
            ',f3.code_sous_famille AS f3, f3.libelle AS f3_fr',
            ',f2.code_famille AS f2, f2.libelle AS f2_fr',
            ',f1.code_rayon AS f1, f1.libelle AS f1_fr',
            'FROM produit',
            self::joinParents(),
//            self::joinRightsByFamilySelectins($user),
            'WHERE actif = 1',
            '/*check nesting level*/',
            'AND f1.code_rayon IS NOT NULL',
            'GROUP BY f3'
        ]);
    }

    static function selectTotalFields()
    {
        return implode(', ', [
            'COUNT(code_produit) AS total',
            'COUNT(DISTINCT code_marque) AS marque_produit',
            //'SUM(quantity) AS quantity',
//            'ROUND(SUM(prix * pcb_produit * quantity), 2)AS valeur',
//            'ROUND(SUM(pcb_produit * quantity), 2) AS uvc',
//            "ROUND(SUM(REPLACE(poidsBrutUVC,',','.') * pcb_produit * quantity),2) AS poids",
            //"ROUND(SUM(FORMAT(REPLACE(volumeColis,',','.') * quantity, 4)),2) AS cubage",
        ]);
    }

    static function selectProductTotal($temperature, $user)
    {
        return implode(self::$glue, [
            'SELECT ',
            self::selectTotalFields(),
            'FROM produit',
            self::joinParents(),
            self::joinRightsByFamilySelectins($user),
            //self::joinCommand(),
            //self::joinColis(),

            'WHERE actif = 1',
            'AND temperature = \'' . $temperature . '\'',
        ]);
    }

    static function joinParents()
    {
        return implode(self::$glue, [
            '/*parent structure*/',
            /*'LEFT JOIN famille f5 ON produit.fk_famille = f5.id_famille',
            'LEFT JOIN famille AS f4 ON f5.fk_famille = f4.id_famille',*/
            'LEFT JOIN sous_famille AS f3 ON produit.code_sous_famille = f3.code_sous_famille',
            'LEFT JOIN famille AS f2 ON produit.code_famille = f2.code_famille',
            'LEFT JOIN rayon AS f1 ON produit.code_rayon = f1.code_rayon',
        ]);
    }

    static function joinRightsByFamilySelectins($user)
    {
        return implode(self::$glue, [
            '/*user selections*/',
            'INNER JOIN user_family_selections AS usf ON (produit.id_produit=usf.entity',
            'OR f1.id_famille=usf.entity',
            'OR f2.id_famille=usf.entity',
            'OR f3.id_famille=usf.entity',
            'OR f4.id_famille=usf.entity',
            'OR f5.id_famille=usf.entity)',
            'AND usf.user_id = ' . $user->getId(),
        ]);
    }

    static function joinRights($user)
    {
        return implode(self::$glue, [
            '/*rights*/',
            'INNER JOIN user_product_selections AS ups ON produit.code_produit = ups.entity',
            'AND ups.user_id = ' . $user->getId(),
        ]);
    }

    static function joinCommand()
    {
        return implode(self::$glue, [
            '/*commande*/',
            'LEFT JOIN commande_produit AS cp ON cp.produit_id = produit.code_produit',
            'LEFT JOIN commande ON cp.order_id = commande.id',
            'LEFT JOIN commande_etatcommande AS cec ON cec.order_id = commande.id',
            'LEFT JOIN etatcommande AS ec ON ec.id = cec.orderStatus_id AND ec.clé = \'STATUS_CURRENT\'',
        ]);
    }

    static function joinColis()
    {
        return implode(self::$glue, [
            '/*first colis*/',
            'LEFT JOIN colis ON colis.id = (' . self::selectColisId('produit.id_produit') . ')',
        ]);
    }

    static function joinPhoto()
    {
        return implode(self::$glue, [
            '/*last photo*/',
            'LEFT JOIN photo ON photo.id_photo = (' . self::selectLastPhotoId('produit.code_produit') . ')',
        ]);
    }

    static function joinMarque()
    {
        return self::selectMarqueId('produit.code_produit');
    }

    static function selectProduitId($user)
    {
        return implode(self::$glue, [
            'SELECT produit.id_produit FROM produit',
            self::joinParents(),
            self::joinRights($user),
        ]);
    }

    static function filterFamily($filter)
    {
        if (empty($filter)) {
            return '';
        }

        $filterIn = implode(',', $filter);
        return implode(self::$glue, [
            '/*filter family*/',
            'AND (produit.fk_famille IN (' . $filterIn . ')',
            'OR f1.id_famille IN (' . $filterIn . ')',
            'OR f2.id_famille IN (' . $filterIn . ')',
            'OR f3.id_famille IN (' . $filterIn . ')',
            'OR f4.id_famille IN (' . $filterIn . ')',
            'OR f5.id_famille IN (' . $filterIn . '))',
        ]);
    }

    static function selectColisId($productId)
    {
        return implode(self::$glue, [
            'SELECT id FROM colis',
            'WHERE colis.fk_produit = ' . $productId . ' LIMIT 1',
        ]);
    }

    static function selectLastPhotoId($productId)
    {
        return implode(self::$glue, [
            'SELECT id_photo FROM photo',
            'WHERE photo.code_produit = ' . $productId . ' ORDER BY id_photo DESC LIMIT 1',
        ]);
    }

    static function selectMarqueId($productId)
    {
        return implode(self::$glue, [
            'LEFT JOIN marque ON marque.code_marque = produit.code_marque',
        ]);
    }

    static function data_output($columns, $data)
    {
        return array_map(function ($row) use ($columns) {
            return array_reduce($columns,
                function ($memo, $column) use ($row) {
                    $memo[$column['dt']] = isset($column['formatter']) ? $column['formatter']($row[$column['db']]) : $row[$column['db']];
                    return $memo;
                },
                []);
        },
            $data);

        return $out;
    }

    static function limit($request)
    {
        $limit = '';

        if (isset($request['start']) && $request['length'] != -1) {
            $limit = "LIMIT " . intval($request['start']) . ", " . intval($request['length']);
        }

        return $limit;
    }

    static function order($request, $columns)
    {
        $order = '';

        if (isset($request['order']) && count($request['order'])) {
            $orderBy = array();
            $dtColumns = array_column($columns, 'dt');

            for ($i = 0, $ien = count($request['order']); $i < $ien; $i++) {
                // Convert the column index into the column data property
                $columnIdx = intval($request['order'][$i]['column']);
                $requestColumn = $request['columns'][$columnIdx];

                $columnIdx = array_search($requestColumn['data'], $dtColumns);
                $column = $columns[$columnIdx];

                if ($requestColumn['orderable'] == 'true') {
                    $dir = $request['order'][$i]['dir'] === 'asc' ?
                        'ASC' :
                        'DESC';

                    $orderBy[] = '`' . $column['db'] . '` ' . $dir;
                }
            }

            $order = 'ORDER BY ' . implode(', ', $orderBy);
        }

        return $order;
    }


    static function filter($request, $columns, &$bindings)
    {
        $globalSearch = array();
        $columnSearch = array();
        $dtColumns = array_column($columns, 'dt');

        if (isset($request['search']) && $request['search']['value'] != '') {
            $str = $request['search']['value'];

            for ($i = 0, $ien = count($request['columns']); $i < $ien; $i++) {
                $requestColumn = $request['columns'][$i];
                $columnIdx = array_search($requestColumn['data'], $dtColumns);
                $column = $columns[$columnIdx];

                if ($requestColumn['searchable'] == 'true') {
                    $binding = self::bind($bindings, '%' . $str . '%', \PDO::PARAM_STR);
                    $globalSearch[] = "`" . $column['db'] . "` LIKE " . $binding;
                }
            }
        }

        // Individual column filtering
        if (isset($request['columns'])) {
            for ($i = 0, $ien = count($request['columns']); $i < $ien; $i++) {
                $requestColumn = $request['columns'][$i];
                $columnIdx = array_search($requestColumn['data'], $dtColumns);
                $column = $columns[$columnIdx];

                $str = $requestColumn['search']['value'];

                if ($requestColumn['searchable'] == 'true' &&
                    $str != ''
                ) {
                    $binding = self::bind($bindings, '%' . $str . '%', \PDO::PARAM_STR);
                    $columnSearch[] = "`" . $column['db'] . "` LIKE " . $binding;
                }
            }
        }

        // Combine the filters into a single string
        $where = '';

        if (count($globalSearch)) {
            $where = '(' . implode(' OR ', $globalSearch) . ')';
        }

        if (count($columnSearch)) {
            $where = $where === '' ?
                implode(' AND ', $columnSearch) :
                $where . ' AND ' . implode(' AND ', $columnSearch);
        }

        if ($where !== '') {
            $where = 'AND ' . $where;
        }

        return $where;
    }


    static function fatal($msg)
    {
        echo json_encode(array(
            "error" => $msg
        ));

        exit(0);
    }

    static function bind(&$a, $val, $type)
    {
        $key = ':binding_' . count($a);

        $a[] = array(
            'key' => $key,
            'val' => $val,
            'type' => $type
        );

        return $key;
    }

    static function filter_key($filter, $array)
    {
        return array_reduce($filter,
            function ($memo, $key) use ($array) {
                if (array_key_exists($key, $array)) {
                    $memo[$key] = $array[$key];
                }
                return $memo;
            },
            []);
    }

    static function _flatten($a, $join = ' AND ')
    {
        if (!$a) {
            return '';
        } else if ($a && is_array($a)) {
            return implode($join, $a);
        }
        return $a;
    }
}


