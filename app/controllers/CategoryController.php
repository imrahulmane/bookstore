<?php


namespace App\controllers;


use App\providers\BookCategoryDataProvider;
use App\providers\BookDataProvider;
use MongoDB\BSON\ObjectId;

class CategoryController
{

    public function createCategory($data) {
        $bookCategoryProvider = new BookCategoryDataProvider();
        $isCategoryListed = $bookCategoryProvider->findOne($data);

        if(is_null($isCategoryListed)){
            $bookCategoryProvider->insertOne($data);
            return [
                'status' => 'Success',
                'message' => "Category Created SuccessFully"
            ];
        }

        return [
            'status' => 'Failed',
            'message' => 'Category is already listed'
        ];

    }

    public function updateCategory($id, $data){
        $searchArray = ['_id' => new ObjectId($id)];
        $updateArray = ['$set' => $data];
        $bookCategoryProvider = new BookCategoryDataProvider();
        $result = $bookCategoryProvider->updateOne($searchArray, $updateArray);

        if($result === 0) {
            //if updated count is 0 then nothing is updated
            return [
                'status' => 'Failed',
                'message' => 'There are no categories available'
            ];
        }

        return [
            'status' => 'Success',
            'message' => 'Category Updated Successfully'
        ];

    }

    public function getCategory($id)
    {
        $searchArray = ['_id' => new ObjectId($id)];
        $bookCategoryProvider = new BookCategoryDataProvider();
        $result = $bookCategoryProvider->findOne($searchArray);

        $result['count'] = $this->getBookCategoryCount($id);

        if(is_null($result)){
            return [
                'status' => 'Failed',
                'message' => 'There are no categories available'
            ];
        }

        return $result;
    }

    public function getAllCategories() {
        $bookCategoryProvider = new BookCategoryDataProvider();
        $categories = $bookCategoryProvider->find();

        if(empty($categories)) {
            return [
                'status' => 'Success',
                'message' => 'There are no categories available'
            ];
        }

        foreach ($categories as $key => $category) {
            $categories[$key]['_id'] = (string) $category['_id'];
            $categories[$key]['count'] = $this->getBookCategoryCount($category['_id']);
        }

        return $categories;
    }

    public function deleteCategory($id, $moveId) {
        //TODO: move books to the other category and delete
        if($moveId) {
            $searchArray = ['category_id' => (string)$id];
            $updateArray = ['$set' => [
                'category_id' => $moveId
            ]];

            $booksDataProvider = new BookDataProvider();
            $booksDataProvider->updateMany($searchArray, $updateArray);
        }

        $deleteSearchArray = ['_id' => new ObjectId($id)];
        $bookCategoryProvider = new BookCategoryDataProvider();
        $result =  $bookCategoryProvider->deleteOne($deleteSearchArray);

        if($result === 0){
            return [
                'status' => 'Failed',
                'message' => 'There is no such category with provided id'
            ];
        }
        return [
            'status' => 'Success',
            'message' => 'Category Deleted Successfully'
        ];
    }

    public function getBookCategoryCount($categoryId){
        $searchArray = ['category_id' => (string) $categoryId];
        $booksDataProvider = new BookDataProvider();
        $bookCount = $booksDataProvider->recordCount($searchArray);
        return $bookCount;
    }




}