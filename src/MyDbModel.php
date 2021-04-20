<?php


namespace Server;

use mysqli;
use PDO;

class MyDbModel extends DbModel {
    protected $queries = [
        "getAllClients" => "SELECT  
client.id as id, 
category.name as category, 
firstname, 
lastname, 
email, 
gender.name as gender,  
birthDate
FROM test.client
join test.gender on gender.id=client.gender_id
join test.category on category.id=client.category_id;",
        "getAllCategories" => "select * from test.category;",
        "getAllGenders" => "select * from test.gender;",
        "createTableCategory" => "CREATE TABLE test.category (
	id BIGINT UNSIGNED auto_increment NOT NULL,
	name varchar(20) NOT NULL,
	CONSTRAINT category_PK PRIMARY KEY (id)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_0900_ai_ci;",
        "createTableGender" => "CREATE TABLE test.gender (
	id BIGINT UNSIGNED auto_increment NOT NULL,
	name VARCHAR(10) NOT NULL,
	CONSTRAINT gender_PK PRIMARY KEY (id)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_0900_ai_ci;",
        "createTableClient" => "CREATE TABLE test.`client` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL,
  `firstname` varchar(11) NOT NULL,
  `lastname` varchar(13) NOT NULL,
  `email` varchar(38) NOT NULL,
  `gender_id` bigint unsigned NOT NULL,
  `birthDate` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;",
        "createDatabase" => "create database IF NOT EXISTS test
CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;",
        "fetchTables" => "select TABLE_NAME, TABLE_ROWS from information_schema.TABLES
where TABLE_SCHEMA = 'test';",
    ];
    protected $queriesWithResult = [
        "getAllClients" => NULL,
        "getAllCategories" => NULL,
        "getAllGenders" => NULL,
        "fetchTables" => NULL,
    ];//associative array for hash key lookup (seems faster)
    protected $insertFieldsOrder = [
    ];

    function __construct(&$connection) {
        parent::__construct($connection);
    }

    public function checkTables() {
        $tables = $this->execPrepared("fetchTables", []);
        $tablesNumRows = [];
        foreach ($tables as $record) {
            if (!array_key_exists("TABLE_NAME", $record))
                continue;
            $tablesNumRows[$record["TABLE_NAME"]] = $record["TABLE_ROWS"];
        }
        return $tablesNumRows;
    }

    public function createNeededTables(array $tablesPresent) {
        $tablesData = [
            ["category", "createTableCategory"],
            ["gender", "createTableGender"],
            ["client", "createTableClient"],
        ];
        foreach ($tablesData as $descr) {
            if (!array_key_exists($descr[0], $tablesPresent)) {
                $this->execPrepared($descr[1], []);
            }
        }
    }

    public function getAllClients() {
        return $this->execPrepared("getAllClients", []);
    }

    public function checkDatabaseAndTables() {
        $this->execPrepared("createDatabase", []);
        $this->createNeededTables(
            $this->checkTables()
        );
        //TODO: make this not an excessive query but a try catch thing
    }
}