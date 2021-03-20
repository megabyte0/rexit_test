<?php


namespace Server;

use mysqli;
use PDO;


class DbModel {
    protected $connection;
    protected $prepared = [];
    protected $queries;
    protected $queriesWithResult;
    protected $insertFieldsOrder;

    function __construct(&$connection) {
        $this->connection = $connection;
        foreach ($this->queries as $key => $query) {
            $this->prepared[$key] = $this->connection->prepare($query);
        }
    }

    public function execPrepared($key, $params) {
        $stmt = $this->prepared[$key];
        //var_dump($stmt);die;
        if (is_a($stmt, "PDOStatement")) {
            $result = $stmt->execute($params);
            if (!array_key_exists($key, $this->queriesWithResult)) {
                if ($result === false) {
                    var_dump($stmt->errorInfo());
                }
                return $result;
            } else {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } elseif (is_a($stmt, "mysqli_stmt")) {
            if (count($params)) {
                call_user_func_array(array($stmt, "bind_param"), $params);
            }
            //https://stackoverflow.com/a/60496
            $stmt->execute();
            $result = $stmt->get_result();
            if (!array_key_exists($key, $this->queriesWithResult)) {
                return $result;
            } else {
                $resultAssocArray = [];
                while ($row = $result->fetch_assoc()) {
                    $resultAssocArray[] = $row;
                }
                return $resultAssocArray;
            }
        } else {
            return NULL;
        }
    }

    protected function insert($items, $key, $table, $generateId) {
        $stmt = $this->prepared[$key];
        $generatedId = 0;
        $resultIds = [];
        foreach ($items as $item) {
            $generatedId += 1;//I'm aware about ++, it's a python style
            $row = [];
            foreach ($this->insertFieldsOrder[$table] as $field) {
                if (!array_key_exists($field, $item)) {
                    if (($field === "id") && ($generateId)) {
                        $fieldValue = $generatedId;
                    } else {
                        $fieldValue = NULL;
                    }
                } else {
                    $fieldValue = $item[$field];
                }
                $row[] = $fieldValue;
            }
            if (is_a($stmt, "PDOStatement")) {
                $this->execPrepared($key, $row);
                $resultIds[] = $this->connection->lastInsertId();
            } elseif (is_a($stmt, "mysqli_stmt")) {
                $fieldTypes = implode("",
                    array_map(function ($elem) {
                        if (is_a($elem, "int")) {
                            return "i";
                        } elseif (is_double($elem) || is_float($elem)) {
                            return "d";// https://stackoverflow.com/a/24533443
                        } else {
                            return "s";
                        }
                    }, $row)
                );
                $this->execPrepared($key, array_merge([$fieldTypes], $row));
                $resultIds[] = $this->connection->insert_id;
            }
        }
        return $resultIds;
    }

    public function batchInsert($items, $table, $fields, $mysqliFieldTypes) {
        $sql = sprintf("insert into %s (%s) values %s;",
        $table,
        implode(", ",array_map(
            function ($field) {return "`$field`";},
            $fields)),
            substr(str_repeat(
                "(".substr(str_repeat("?, ",count($fields)),0,-2)."), "
                , count($items)
            ),0,-2)
        );
//        var_dump($sql);
        // https://stackoverflow.com/a/46861938
        $values = array_merge(...array_map(function ($item) use ($fields) {
            $res = [];
            foreach ($fields as $field) {
                if (array_key_exists($field,$item)) {
                    $res[] = $item[$field];
                } else {
                    $res[] = NULL;
                }
            }
            return $res;
        },$items));
        $stmt = $this->connection->prepare($sql);
//        var_dump($stmt);
        $preparedKey = "batchInsert".$table;
        $this->prepared[$preparedKey] = $stmt;

        if (is_a($stmt, "PDOStatement")) {
            $res = $this->execPrepared($preparedKey, $values);
            //var_dump($res);
        } elseif (is_a($stmt, "mysqli_stmt")) {
            $this->execPrepared($preparedKey, array_merge(
                [str_repeat($mysqliFieldTypes,count($items))],
                $values)
            );
        }
        unset($this->prepared[$preparedKey]);
    }
}