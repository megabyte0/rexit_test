<?php


namespace Server;


class MyController {
    protected $db;

    function __construct(MyDbModel &$db) {
        $this->db=$db;
    }
    //no views, not needed, pure static or json
    public function getUsersWithPosts() {
        $users = $this->db->getAllUsers();
        $posts = $this->db->getAllPosts();
        $usersIdMap=[];
        foreach ($users as $user) {
            $usersIdMap[$user["id"]]=$user;
        }
        foreach ($posts as $post) {
            if (!array_key_exists("Posts",$usersIdMap[$post["UserId"]])) {
                $usersIdMap[$post["UserId"]]["Posts"]=[];
            }
            $usersIdMap[$post["UserId"]]["Posts"][$post["id"]]=$post;
        }
        return $usersIdMap;
    }

    public function getStatic($fileName) {
        return function () use ($fileName) {
            return file_get_contents($fileName);
        };
    }
}