<?php


namespace Server;


use Exception;
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

    public function storeProductOrReview($table) {
        // https://stackoverflow.com/a/18867369
        $data = json_decode(file_get_contents('php://input'), true);
        if ($table === 'review') {
            $n = $this->db->execPrepared("getReviewMaxN", [(int)$data['product_id']])["n"];
            $data['n'] = ($n !== NULL) ? $n + 1 : 0;
        }
        $res = ['success' => true];
        if ($table === 'product') {
            try {
                $picPngData = Image::thumbnail($data['picture']);
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'exception' => $e->getMessage()
                ];
            }
            $data['image'] = $picPngData;
            $res['image'] = base64_encode($picPngData);
        }
        $id = call_user_func(array($this->db, [
            "product" => "insertProduct",
            "review" => "insertReview"
        ][$table]), [$data])[0];
        $res['id'] = $id;
        return $res;
    }

    public function checkPicture() {
        $url = json_decode(file_get_contents('php://input'), true);
        try {
            $picPngData = Image::thumbnail($url);
        } catch (Exception $e) {
            return [
                'success' => false,
                'exception' => $e->getMessage()
            ];
        }
        return [
            'success' => true,
            'image' => base64_encode($picPngData)
        ];
    }
}