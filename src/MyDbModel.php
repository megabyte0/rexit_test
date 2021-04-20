<?php


namespace Server;

use mysqli;
use PDO;

class MyDbModel extends DbModel {
    #https://stackoverflow.com/a/2533913
    protected $sqlSelectAge = "
DATE_FORMAT(NOW(), '%Y') -
DATE_FORMAT(birthDate, '%Y') -
(DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(birthDate, '00-%m-%d'))";
    protected $queries = [
        "getAllClients" => "SELECT  
client.id as id, 
category.name as category, 
firstname, 
lastname, 
email, 
gender.name as gender,  
cast(birthDate as char) as birthDate
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

    public function getAllClients($params) {
        return $this->execPrepared("getAllClients", []);
    }

    protected function generateSelectClientsSql($params) {
        $sql = [str_replace(["\nFROM",";"],[",\n".
            $this->sqlSelectAge." as age\n".
            "FROM",""],$this->queries["getAllClients"])];
        list($sqlWherePart, $paramsWhere) = $this->generateSelectClientWhere($params);
        $sql[]=$sqlWherePart;
        list($sqlLimitPart, $paramsLimit) = $this->generateSelectClientLimit($params);
        $sql[]=$sqlLimitPart;
        return array(implode(' ',$sql),array_merge($paramsWhere,$paramsLimit));
    }

    protected function generateSelectClientWhere($params) {
        $intArray = function ($s) {
            return [(int)$s];
        };
        $intArrayPlusOne = function ($s) {
            return [((int)$s)+1];
        };
        $whereDict = [
            'category_id' => ['category_id = ?', $intArray],
            'gender_id' => ['gender_id = ?', $intArray],
            'age' => ["(birthDate > date_sub(curdate(),interval ? year)) and
(birthDate <= date_sub(curdate(),interval ? year))", function ($s) {
                return [((int)$s) + 1, (int)$s];
            }],
            'min_age' => ['birthDate <= date_sub(curdate(),interval ? year)', $intArray],
            'max_age' => ['birthDate > date_sub(curdate(),interval ? year)', $intArrayPlusOne],
            'bday' => ['day(birthDate) = ?', $intArray],
            'bmonth' => ['month(birthDate) = ?', $intArray],
            'byear' => ['year(birthDate) = ?', $intArray],
        ];
    }

    protected function generateSelectClientLimit($params) {
    }

    public function checkDatabaseAndTables() {
        $this->execPrepared("createDatabase", []);
        $tableRowsCount = $this->checkTables();
        $this->createNeededTables(
            $tableRowsCount
        );
        return $tableRowsCount;
        //TODO: make this not an excessive query but a try catch thing
    }
}