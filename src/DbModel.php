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
                            return "f";
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

    protected function bulkInsert($items, $table, $fields) {
        $sql = sprintf("insert into %s (%s) values %s;",
        $table,
        implode(", ",array_map(
            function ($field) {return "`$field`";},
            $fields))
        );
    }
}