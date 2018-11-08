<?php

namespace Sogedial\SiteBundle\Service;


class DataTableCatalogue
{

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
        $resTotalLength = self::sql_exec(self::selectProductTotal($temperature, $user));
        $recordsTotal = isset($resTotalLength[0]['total']) ? $resTotalLength[0]['total'] : 0;

        $totalColumns = array_filter($columns, function ($item) {
            return !empty($item['total']);
        });

        $total = self::data_output($totalColumns, $resTotalLength);
        $total = reset($total);

        return [
            'total' => $total,
            'draw' => isset($request['draw']) ? intval($request['draw']) : 1,
            'recordsTotal' => intval($recordsTotal),
            'recordsFiltered' => intval($recordsFiltered),
            'data' => self::data_output($columns, $data)
        ];
    }
}