<?php


namespace App\controllers;


use App\providers\BookCategoryDataProvider;
use App\providers\BookDataProvider;
use MongoDB\BSON\ObjectId;

class BooksController
{
    private $returnFailedMessage =  [
        'status' => 'Failed',
        'message' => "Book with Given ID, Didn't Found"
    ];

    private $returnSuceessMessage =  [
                'status' => 'Success',
                'message' => 'Book added successfully'
    ];

    public function addBook($data) {
        $bookDataProvider = new BookDataProvider();
        $data['price'] = (int) $data['price'];
        $searchArray = ['title' => $data['title']];
        $isBookListed = $bookDataProvider->findOne($searchArray);

        if(is_null($isBookListed)){
            $bookDataProvider->insertOne($data);
            return $this->returnSuceessMessage;
        }

        return [
            'status' => 'Failed',
            'message' => 'Given book is already listed'
        ];
    }

    public function updateBook($id, $data) {
        $searchArray = ['_id' => new ObjectId($id)];
        $updateArray = ['$set' => $data];
        $booksDataProvider = new BookDataProvider();
        $result = $booksDataProvider->updateOne($searchArray, $updateArray);

        if($result === 0) {
            return $this->returnFailedMessage;
        }

        return $this->returnSuceessMessage;
    }

    public function getBookDetails($id) {
        $searchArray = ['_id' => new ObjectId($id)];
        $booksDataProvider = new BookDataProvider();
        $book = $booksDataProvider->findOne($searchArray);

        if(is_null($book)) {
            return $this->returnFailedMessage;
        }
        $categoryName = $this->getCategoryName($book['category_id']);
        $book['category_name'] = $categoryName;
        return $book;
    }

    public function getAllBooks() {
        $booksDataProvider = new BookDataProvider();
        $books = $booksDataProvider->find();

        if(empty($books)) {
            return [
                'status' => 'Failed',
                'message' => 'There are no books available'
            ];
        }

        foreach ($books as $key => $book) {
            $books[$key]['_id'] = (string) $book['_id'];
            $categoryName = $this->getCategoryName($book['category_id']);
            $books[$key]['category_name'] = $categoryName;
        }

        return $books;
    }

    public function deleteBook($id) {
        $searchArray = ['_id' => new ObjectId($id)];
        $booksDataProvider = new BookDataProvider();
        $result = $booksDataProvider->deleteOne($searchArray);

        if($result === 0) {
            return $this->returnFailedMessage;
        }

        return  [
            'status' => 'Success',
            'message' => 'Book removed successfully'
        ];
    }

    private function getCategoryName($category_id) {
        $categoryDataProvider = new BookCategoryDataProvider();
        $categorySearchArray = ['_id' => new ObjectId($category_id)];
        $projection = ['_id' => 0 ,'name' => 1 ];
        $category = $categoryDataProvider->findOne($categorySearchArray, $projection);
        return $category['name'];
    }
}