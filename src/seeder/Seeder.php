<?php

namespace Seeder;

use \Server\MyDbModel;
use PDO;
use mysqli;

class Seeder {
    protected $dbModel;
    protected $tablesRowCount;

    function __construct(&$dbModel) {
        $this->dbModel = $dbModel;
    }

    public function connect() {
        //TODO: dead code, remove
        //https://forums.docker.com/t/app-container-cannot-access-mysql-container/6660/2

        $connection = new PDO('mysql:host=mysql', 'root', '12345678');
        //$connection = new mysqli('mysql', 'root', '12345678');
        $db = (new \Server\MyDbModel($connection));
        $db->checkDatabaseAndTables();
        $this->dbModel = $db;
    }

    protected static function readFileIntoCsv($fileName, $fieldsNumber) {
        ini_set('memory_limit', '1.5G');
        $raw_csv = file_get_contents($fileName);
        $csv_lines = explode("\n", $raw_csv);
        $csv_fields = array_map(function ($line) {
            return explode(",", $line);
        }, $csv_lines);
        $csv_fields_filtered = array_filter($csv_fields, function ($item) use ($fieldsNumber) {
            return count($item) == $fieldsNumber;
        });
        $csv_header = $csv_fields[0];
        $csv = array_map(function ($item) use ($csv_header) {
            $res = [];
            foreach ($item as $key => $value) {
                $res[$csv_header[$key]] = $value;//this line crushes for memory
            }
            return $res;
        }, array_slice($csv_fields_filtered, 1));
        return $csv;
    }

    public function seed() {
        $csv = self::readFileIntoCsv("dataset.txt", 6);

        //get unique values for category and gender
        $dictionaries = array_map(function ($key) use ($csv) {
            return array_unique(array_column($csv, $key));
        }, ["category" => "category", "gender" => "gender"]);

        $this->fillDbDictionaries($dictionaries);
        $ids = $this->getDictionariesDbIds($dictionaries);

        //insert the ids into the csv data
        //not got too used to passing references in php
        //thus not refactored into a method
        foreach ($csv as $index => $item) {
            foreach ($ids as $key => $ids_key) {
                $csv[$index][$key . "_id"] = $ids_key[$item[$key]];
            }
        }

        //the batch insert
        //https://bugs.mysql.com/bug.php?id=101630
        //60 000 placeholders at a time
        $chunkSize = 10000;
        $csvRecordsCount = count($csv);//100 000
        for ($i = 0; $i * $chunkSize < $csvRecordsCount; ++$i) {
            $this->dbModel->batchInsert(
                array_slice($csv, $chunkSize * $i, $chunkSize),
                "test.client",
                ["category_id", "firstname", "lastname", "email", "gender_id", "birthDate"],
                "isssis"
            );
        }
    }

    protected function fillDbDictionaries(array $dictionaries) {
        foreach ($dictionaries as $key => $value) {
            $value_assoc = array_map(function ($item) {
                return ["name" => $item];
            }, $value);
            $this->dbModel->batchInsert($value_assoc, "test." . $key, ["name"], "s");
        }
    }

    protected function getDictionariesDbIds(array $dictionaries) {
        $ids = [];
        foreach ($dictionaries as $key => $value) {
            $temp_ids = $this->dbModel->execPrepared([
                "category" => "getAllCategories",
                "gender" => "getAllGenders",
            ][$key], []);
            //var_dump($temp_ids);
            $ids[$key] = [];
            foreach ($temp_ids as $id_name) {
                $ids[$key][$id_name["name"]] = $id_name["id"];
            }
        }
        return $ids;
    }
}