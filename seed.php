<?php

require __DIR__ . '/vendor/autoload.php';

ini_set('memory_limit', '1.5G');

//https://forums.docker.com/t/app-container-cannot-access-mysql-container/6660/2

$connection = new PDO('mysql:host=mysql', 'root', '12345678');
//$connection = new mysqli('mysql','root','12345678');
$db = (new \Server\MyDbModel($connection));
$db->checkDatabaseAndTables();
function readFileIntoCsv($fileName, $fieldsNumber) {
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
            $res[$csv_header[$key]] = $value;
        }
        return $res;
    }, array_slice($csv_fields_filtered, 1));
    return $csv;
}

$csv = readFileIntoCsv("dataset.txt", 6);
$dictionaries = array_map(function ($key) use ($csv) {
    return array_unique(array_column($csv, $key));
}, ["category" => "category", "gender" => "gender"]);
var_dump($dictionaries);
$ids = [];
foreach ($dictionaries as $key => $value) {
    $value_assoc = array_map(function ($item) {
        return ["name" => $item];
    }, $value);
    $db->batchInsert($value_assoc, "test." . $key, ["name"], "s");
    $temp_ids = $db->execPrepared([
        "category" => "getAllCategories",
        "gender" => "getAllGenders",
    ][$key], []);
    var_dump($temp_ids);
    $ids[$key] = [];
    foreach ($temp_ids as $id_name) {
        $ids[$key][$id_name["name"]] = $id_name["id"];
    }
}
foreach ($csv as $index => $item) {
    foreach ($ids as $key => $ids_key) {
        $csv[$index][$key . "_id"] = $ids_key[$item[$key]];
    }
}
$db->batchInsert($csv, "test.client",
    ["category_id", "firstname", "lastname", "email", "gender_id", "birthDate"],
    "isssis"
);
