<?php


namespace Server;

use mysqli;
use PDO;


class DbModel {
    protected $connection;
    protected $prepared = [];
    protected $queries;
    protected $queriesWithResult;
    protected $fieldsOrder;

    function __construct(&$connection) {
        $this->connection = $connection;
//        if (is_a($connection,"PDO")) {
        foreach ($this->queries as $key => $query) {
            $this->prepared[$key] = $this->connection->prepare($query);
        }
//        } elseif (is_a($connection,"mysqli")) {
//
//        }
    }

    public function execPrepared($key, $params) {
        $stmt = $this->prepared[$key];
        if (is_a($stmt, "PDOStatement")) {
            $result = $stmt->execute($params);
            if (!array_key_exists($key, $this->queriesWithResult)) {
                return $result;
            } else {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } elseif (is_a($stmt, "mysqli_stmt")) {
//            return (new MysqliDbWrapper($this->connection))->preparedSelect(
//                $this->queries[$key],$params
//            );
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
        foreach ($items as $item) {
            $generatedId += 1;//I'm aware about ++, it's a python style
            $row = [];
            foreach ($this->fieldsOrder[$table] as $field) {
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
            } elseif (is_a($stmt, "mysqli_stmt")) {
                $fieldTypes = implode("",
                    array_map(function ($elem) {
                        return is_a($elem, "int") ? "i" : "s";
                    }, $row)
                );
                $this->execPrepared($key, array_merge([$fieldTypes], $row));
            }
        }
    }
}