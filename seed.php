<?php

require __DIR__ . '/vendor/autoload.php';

//https://forums.docker.com/t/app-container-cannot-access-mysql-container/6660/2

$connection = new PDO('mysql:host=mysql','root','12345678');
//$connection = new mysqli('mysql','root','12345678');
$db = (new \Server\MyDbModel($connection));
$db->checkDatabaseAndTables();
$json = json_decode(file_get_contents("data.json"),true);
foreach ($json as $key => $value) {
//    if ($key === "review") {
//        continue;
//    }
    $db->batchInsert($value,"test1.".$key,
        [
            'review' => ['user_name', 'rating', 'comment', 'timestamp', 'product_id', 'n'],
            'product' => ['name', 'picture', 'value', 'timestamp', 'merchant_name', 'id']
        ][$key],
        [
            'review' => "sisiii",
            'product' => "ssdisi"
        ][$key]
    );
    $db->execPrepared([
        'review' => "updateReviewCreatedFromTimestamp",
        'product' => "updateProductCreatedFromTimestamp",
    ][$key],[]);
}
//$controller = new \Server\MyController($db);
//$router = new \Server\Router($controller);
//$router->route($_SERVER['REQUEST_URI']);
