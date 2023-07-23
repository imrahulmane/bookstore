<?php


namespace App\util;
use MongoDB;


class DatabaseUtil
{
    private $dbName="bookstore";

    public function getConnection($collection_name) {
        $client = new MongoDB\Client(getenv("DB_URL"));
        $db = $client->selectDatabase($this->dbName);
        $options = ["typeMap" => ['root' => 'array', 'document' => 'array']];
        $colln = $db->selectCollection($collection_name,$options);
        return $colln;
    }
}