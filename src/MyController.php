<?php


namespace Server;


use Image\Image;

class MyController {
    protected $db;

    function __construct(MyDbModel &$db) {
        $this->db = $db;
    }

    //no views, not needed, pure static or json
    public function getProductsWithReviews() {
        $products = $this->db->getAllProducts();
        //var_dump($products);die;
        $reviews = $this->db->getAllReviews();
        $productsIdMap = [];
        foreach ($products as $product) {
            if ($product["image"]!==NULL) {
                $product["image"]=base64_encode($product["image"]);
            }
            //TODO: auto translate types
            $product["value"] = (double)($product["value"]);
            $product["timestamp"] = (int)($product["timestamp"]);
            $product["reviews"] = [];
            $productsIdMap[(int)($product["id"])] = $product;
        }
        foreach ($reviews as $review) {
            $productId = (int)($review["product_id"]);
//            if (!array_key_exists("reviews", $productsIdMap[$productId])) {
//                $productsIdMap[$productId]["reviews"] = [];
//            }
            foreach (["rating","timestamp","n"] as $field) {
                $review[$field] = (int)$review[$field];
            }
            $productsIdMap[$productId]["reviews"][] = $review;
        }
        return array_values($productsIdMap);
    }

    public function getStatic($fileName) {
        return function () use ($fileName) {
            return file_get_contents($fileName);
        };
    }

    public function getImage($url) {
        $url = urldecode($url);
        $image = Image::thumbnail($url);
        $this->db->updateProductImage($url, $image);
        return $image;
    }
}