<?php


namespace Server;

class MysqliDbWrapper {
    //https://www.php.net/manual/en/mysqli-stmt.prepare.php#90436
    protected $connection;

    public function __construct(&$connection) {
        $this->connection = $connection;
    }

    #run a prepared query
    public function runPreparedQuery($query, $params_r) {
        $stmt = $this->connection->prepare($query);
        $this->bindParameters($stmt, $params_r);

        if ($stmt->execute()) {
            return $stmt;
        } else {
            echo("Error in $stmt: "
                . mysqli_error($this->connection));
            return 0;
        }

    }

    # To run a select statement with bound parameters and bound results.
    # Returns an associative array two dimensional array which u can easily
    # manipulate with array functions.

    public function preparedSelect($query, $bind_params_r) {
        $select = $this->runPreparedQuery($query, $bind_params_r);
        $fields_r = $this->fetchFields($select);

        foreach ($fields_r as $field) {
            $bind_result_r[] = &${$field};
        }

        $this->bindResult($select, $bind_result_r);

        $result_r = array();
        $i = 0;
        while ($select->fetch()) {
            foreach ($fields_r as $field) {
                $result_r[$i][$field] = $$field;
            }
            $i++;
        }
        $select->close();
        return $result_r;
    }


    #takes in array of bind parameters and binds them to result of
    #executed prepared stmt

    private function bindParameters(&$obj, &$bind_params_r) {
        call_user_func_array(array($obj, "bind_param"), $bind_params_r);
    }

    private function bindResult(&$obj, &$bind_result_r) {
        call_user_func_array(array($obj, "bind_result"), $bind_result_r);
    }

    #returns a list of the selected field names

    private function fetchFields($selectStmt) {
        $metadata = $selectStmt->result_metadata();
        $fields_r = array();
        while ($field = $metadata->fetch_field()) {
            $fields_r[] = $field->name;
        }

        return $fields_r;
    }
}