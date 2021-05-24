<?php


namespace App\util;


abstract class BaseDataProvider
{
    protected $dbObj;
    protected $collectionObj;

    public function __construct() {
        $this->dbObj = new DatabaseUtil();
        $this->collectionObj = $this->dbObj->getConnection($this->collection());
    }

    abstract protected function collection();

    public function insertOne($data){
        $result = $this->collectionObj->insertOne($data);
        return $result->isAcknowledged();
    }

    public function updateOne($searchArray, $updateArray) {
        $result = $this->collectionObj->updateOne($searchArray, $updateArray);
        return $result->isAcknowledged();
    }

    public function find($searchArray, $projection = []) {
        return  $this->collectionObj->find($searchArray, ["projection" => $projection])->toArray();
    }

    public function findOne($searchArray, $projection =[]){
        return $this->collectionObj->findOne($searchArray, ['projection' => $projection]);
    }

    public function deleteOne($searchArray) {
        return $this->collectionObj->deleteOne($searchArray);
    }

}