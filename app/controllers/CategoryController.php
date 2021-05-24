<?php


namespace App\controllers;


use App\providers\BookCategoryDataProvider;

class CategoryController
{
    public function createCategory($data) {
        $bookProvider = new BookCategoryDataProvider();
        return $bookProvider->insertOne($data);
    }

    public function updateCategory($searchArray, $updateArray){

    }

    public function deleteCategory($searchArray) {
        //TODO: move books to the other category and delete
    }

    public function listAllCategories() {

    }
}