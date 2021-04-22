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
        if (is_a($this->connection, "PDO")) {
            $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        }
        $this->prepareSqlStatements();
        //var_dump($this->prepared);die;
    }

    public function prepareSqlStatements() {
        foreach ($this->queries as $key => $query) {
            if (!array_key_exists($key, $this->prepared) ||
                $this->prepared[$key] === false) {
                $this->prepared[$key] = $this->connection->prepare($query);
            }
        }
    }

    public function isCreateTablesNeeded() {
        foreach ($this->prepared as $stmt) {
            if ($stmt === false) {
                return true;
            }
        }
        return false;
    }

    public function execSql(
        $sql,
        array $values,
        $preparedKey,
        $mysqliFieldTypesStr,
        $resultNeeded = false
    ) {
//        var_dump($sql);
        $stmt = $this->connection->prepare($sql);
//        var_dump($stmt);
//        if ($stmt === false) {
//            var_dump(
//                $this->connection->errorCode(),
//                $this->connection->errorInfo()
//            );
//        }
        $this->prepared[$preparedKey] = $stmt;
        if ($resultNeeded) {
            $this->queriesWithResult[$preparedKey] = NULL;
        }

        if (is_a($stmt, "PDOStatement")) {
            $res = $this->execPrepared($preparedKey, $values);
            //var_dump($res);
        } elseif (is_a($stmt, "mysqli_stmt")) {
            $res = $this->execPrepared($preparedKey, array_merge(
                    [$mysqliFieldTypesStr],
                    $values)
            );
        }
        unset($this->prepared[$preparedKey]);
        if ($resultNeeded) {
            unset($this->queriesWithResult[$preparedKey]);
        }
        return $res;
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
                //call_user_func_array(array($stmt, "bind_param"), $params);
                //https://stackoverflow.com/a/46724803
                $stmt->bind_param(...$params);
            }
            //https://stackoverflow.com/a/60496
            $stmt->execute();//TODO: pass the result here for queries with no result
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
            implode(", ", array_map(
                function ($field) {return "`$field`";},
                $fields)
            ),
            substr(str_repeat(
                "(" . substr(str_repeat("?, ", count($fields)), 0, -2) . "), "
                , count($items)
            ), 0, -2)
        );
//        var_dump($sql);
        // https://stackoverflow.com/a/46861938
        $values = array_merge(...array_map(function ($item) use ($fields) {
            $res = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $item)) {
                    $res[] = $item[$field];
                } else {
                    $res[] = NULL;
                }
            }
            return $res;
        }, $items));
        $preparedKey = "batchInsert" . $table;
        $mysqliFieldTypesStr = str_repeat($mysqliFieldTypes, count($items));
        $res = $this->execSql($sql, $values, $preparedKey, $mysqliFieldTypesStr);
        return $res;
    }
}