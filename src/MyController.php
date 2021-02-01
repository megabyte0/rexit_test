<?php


namespace Server;


class MyController {
    protected $db;

    function __construct(MyDbModel &$db) {
        $this->db = $db;
    }

    //no views, not needed, pure static or json
    public function getUsersWithPosts() {
        $users = $this->db->getAllUsers();
        $posts = $this->db->getAllPosts();
        $usersIdMap = [];
        foreach ($users as $user) {
            $usersIdMap[(int)($user["id"])] = $user;
        }
//        var_dump($users,$posts,$usersIdMap);die;
        foreach ($posts as $post) {
            $userId = (int)($post["userId"]);
            if (!array_key_exists("Posts", $usersIdMap[$userId])) {
                $usersIdMap[$userId]["Posts"] = [];
            }
            $usersIdMap[$userId]["Posts"][$post["id"]] = $post;
        }
        return $usersIdMap;
    }

    public function getStatic($fileName) {
        return function () use ($fileName) {
            return file_get_contents($fileName);
        };
    }
}