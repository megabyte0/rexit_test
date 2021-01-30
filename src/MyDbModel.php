<?php


namespace Server;

use mysqli;
use PDO;

class MyDbModel {
    protected $connection;
    protected $queries = [
        "getAllUsers"=>"select * from test.`Users`;",
        "getAllPosts"=>"select * from test.`Posts`;",
        "insertUser"=>NULL,
        "insertPost"=>NULL,
        "createTableUsers"=>"CREATE TABLE test.Users (
	id BIGINT UNSIGNED NOT NULL,
	first_name varchar(255) NULL,
	last_name varchar(255) NULL,
	phone varchar(20) NULL,
	email varchar(255) NULL,
	CONSTRAINT Users_PK PRIMARY KEY (id)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_0900_ai_ci;",
        "createTablePosts"=>"CREATE TABLE test.Posts (
	id BIGINT UNSIGNED NOT NULL,
	userId BIGINT UNSIGNED NOT NULL,
	title varchar(1000) NULL,
	body MEDIUMTEXT NULL,
	CONSTRAINT Posts_PK PRIMARY KEY (id),
	CONSTRAINT Posts_FK FOREIGN KEY (userId) REFERENCES test.Users(id)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_0900_ai_ci;",
        "createDatabase"=>"create database IF NOT EXISTS test
CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;",
        "fetchTables"=>"select TABLE_NAME from information_schema.TABLES
where TABLE_SCHEMA = 'test';",
        ];
    protected $queriesWithResult = [
        "getAllUsers"=>NULL,
        "getAllPosts"=>NULL,
        "fetchTables"=>NULL,
    ];//associative array for hash key lookup (seems faster)
    protected $tables = ["Users"=>NULL];
    protected $prepared=[];

    function __construct(&$connection) {
        $this->connection = $connection;
//        if (is_a($connection,"PDO")) {
            foreach ($this->queries as $key => $query) {
                $this->prepared[$key]=$this->connection->prepare($query);
            }
//        } elseif (is_a($connection,"mysqli")) {
//
//        }
    }

    public function execPrepared($key,$params) {
        $stmt = $this->prepared[$key];
        if (is_a($stmt,"PDOStatement")) {
            $result = $stmt->execute($params);
            if (!array_key_exists($key,$this->queriesWithResult)) {
                return $result;
            } else {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } elseif (is_a($stmt,"mysqli_stmt")) {
//            return (new MysqliDbWrapper($this->connection))->preparedSelect(
//                $this->queries[$key],$params
//            );
            if (count($params)) {
                call_user_func_array(array($stmt, "bind_param"), $params);
            }
            //https://stackoverflow.com/a/60496
            $stmt->execute();
            $result = $stmt->get_result();
            if (!array_key_exists($key,$this->queriesWithResult)) {
                return $result;
            } else {
                $resultAssocArray = [];
                while ($row = $result->fetch_assoc()) {
                    $resultAssocArray[]=$row;
                }
                return $resultAssocArray;
            }
        } else {
            return NULL;
        }
    }

    public function checkTables() {
        $tables=$this->execPrepared("fetchTables",[]);
        $tablesPresent=[];
        foreach ($tables as $record) {
            if (!array_key_exists("TABLE_NAME",$record))
                continue;
            $tablesPresent[$record["TABLE_NAME"]]=NULL;
        }
        //$this->createNeededTables($tablesPresent);
        return $tablesPresent;
    }

    public function createNeededTables(array $tablesPresent) {
        $tablesData = [
            ["Users", "createTableUsers"],
            ["Posts", "createTablePosts"],
        ];
        foreach ($tablesData as $descr) {
            if (!array_key_exists($descr[0], $tablesPresent)) {
                $this->execPrepared($descr[1], []);
            }
        }
    }

    public function checkPosts() {

    }

    public function insertPosts() {

    }
}