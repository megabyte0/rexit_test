<?php


namespace Server;

use mysqli;
use PDO;

class MyDbModel extends DbModel {
    protected $queries = [
        "getAllProducts" => "select name
, picture
, value
, unix_timestamp(created) as `timestamp`
, merchant_name
, id
, `image` from test1.product;",
        "getAllReviews" => "select user_name
, rating
, comment
, unix_timestamp(created) as `timestamp`
, product_id
, n 
from test1.review;",
//        "insertProduct" => "INSERT INTO test1.product
//(id, first_name, last_name, phone, email)
//VALUES(?, ?, ?, ?, ?);",
//        "insertReview" => "INSERT INTO test1.review
//(id, userId, title, body)
//VALUES(?, ?, ?, ?);",
        "createTableProducts" => "CREATE TABLE test1.`product` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(287) NOT NULL,
  `picture` varchar(128) NOT NULL,
  `value` double DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `merchant_name` varchar(39) NOT NULL,
  `id_wish` varchar(24) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `image` blob,
  PRIMARY KEY (`id`),
  KEY `product_picture_IDX` (`picture`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;",
        "createTableReviews" => "CREATE TABLE test1.`review` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varchar(47) NOT NULL,
  `rating` bigint NOT NULL DEFAULT '0',
  `comment` mediumtext NOT NULL,
  `product_id_wish` varchar(24) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `n` bigint unsigned NOT NULL,
  `timestamp` decimal(17,6) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `product_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `review_n_IDX` (`n`,`product_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=32771 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;",
        "createDatabase" => "create database IF NOT EXISTS test1
CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;",
        "fetchTables" => "select TABLE_NAME from information_schema.TABLES
where TABLE_SCHEMA = 'test1';",
//        "getUsersCount" => "select count(*) as n from test1.`Users`;",
//        "getPostsCount" => "select count(*) as n from test1.`Posts`;",
        "updateProductImage" => "update test1.product set image = ?  
where picture = ?",
    ];
    protected $queriesWithResult = [
        "getAllProducts" => NULL,
        "getAllReviews" => NULL,
        "fetchTables" => NULL,
//        "getUsersCount" => NULL,
//        "getPostsCount" => NULL,
    ];//associative array for hash key lookup (seems faster)
    protected $insertFieldsOrder = [
        "product" => ["id", "userId", "title", "body"],
        "review" => ["id", "first_name", "last_name", "phone", "email"],
    ];
    protected $tables = ["Users" => NULL];

    protected $postsUrl = "http://jsonplaceholder.typicode.com/posts";

    function __construct(&$connection) {
        parent::__construct($connection);
        //$this->check();
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

    public function getAllProducts() {
        return $this->execPrepared("getAllProducts", []);
    }

    public function getAllReviews() {
        return $this->execPrepared("getAllReviews", []);
    }

    public function updateProductImage($url, $image) {
        return $this->execPrepared("updateProductImage", [$image, $url]);
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