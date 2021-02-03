<?php


namespace Server;

use mysqli;
use PDO;
use Person\PersonGenerator;

class MyDbModel extends DbModel {
    protected $queries = [
        "getAllUsers" => "select * from test.`Users`;",
        "getAllPosts" => "select * from test.`Posts`;",
        "insertUser" => "INSERT INTO test.`Users`
(id, first_name, last_name, phone, email)
VALUES(?, ?, ?, ?, ?);",
        "insertPost" => "INSERT INTO test.`Posts`
(id, userId, title, body)
VALUES(?, ?, ?, ?);",
        "createTableUsers" => "CREATE TABLE test.Users (
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
        "createTablePosts" => "CREATE TABLE test.Posts (
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
        "createDatabase" => "create database IF NOT EXISTS test
CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;",
        "fetchTables" => "select TABLE_NAME from information_schema.TABLES
where TABLE_SCHEMA = 'test';",
        "getUsersCount" => "select count(*) as n from test.`Users`;",
        "getPostsCount" => "select count(*) as n from test.`Posts`;",
    ];
    protected $queriesWithResult = [
        "getAllUsers" => NULL,
        "getAllPosts" => NULL,
        "fetchTables" => NULL,
        "getUsersCount" => NULL,
        "getPostsCount" => NULL,
    ];//associative array for hash key lookup (seems faster)
    protected $fieldsOrder = [
        "Posts" => ["id", "userId", "title", "body"],
        "Users" => ["id", "first_name", "last_name", "phone", "email"],
    ];
    protected $tables = ["Users" => NULL];

    protected $postsUrl = "http://jsonplaceholder.typicode.com/posts";

    function __construct(&$connection) {
        parent::__construct($connection);
        $this->check();
    }

    public function checkTables() {
        $tables = $this->execPrepared("fetchTables", []);
        $tablesPresent = [];
        foreach ($tables as $record) {
            if (!array_key_exists("TABLE_NAME", $record))
                continue;
            $tablesPresent[$record["TABLE_NAME"]] = NULL;
        }
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

    public function insertUsers(array $items) {
        $this->insert($items, "insertUser", "Users", True);
    }

    public function insertPosts(array $items) {
        $this->insert($items, "insertPost", "Posts", False);
    }

    public function getAllUsers() {
        return $this->execPrepared("getAllUsers", []);
    }

    public function getAllPosts() {
        return $this->execPrepared("getAllPosts", []);
    }

    public function checkDatabaseAndTables() {
        $this->execPrepared("createDatabase", []);
        $this->createNeededTables(
            $this->checkTables()
        );
    }

    public function checkAndFillData() {
        $fill = [
            "Users" => function () {
                $personGenerator = new PersonGenerator();
                $users = [];
                foreach (range(0, 9) as $_) {
                    $users[] = $personGenerator->generate();
                }
                $this->insertUsers($users);
            },
            "Posts" => function () {
                $postsString = file_get_contents($this->postsUrl);
                $postsJson = json_decode($postsString, true);
                $this->insertPosts($postsJson);
            }
        ];
        foreach ($this->checkDataPresence() as $table => $count) {
            if (!$count) {
                $fill[$table]();
            }
        }
    }

    public function checkDataPresence() {
        $res = [];
        foreach ([
                     "Users" => "getUsersCount",
                     "Posts" => "getPostsCount",
                 ] as $table => $preparedKey) {
            $res[$table] = (int)($this->execPrepared($preparedKey, [])[0]["n"]);
        }
        return $res;
    }

    public function check() {
        $this->checkDatabaseAndTables();
        $this->checkAndFillData();
    }
}