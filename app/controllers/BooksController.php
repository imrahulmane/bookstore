<?php


namespace App\controllers;


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
        $isBookListed = $bookDataProvider->findOne($data);

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
        $result = $booksDataProvider->findOne($searchArray);

        if(is_null($result)) {
            return $this->returnFailedMessage;
        }

        return $result;
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

        $result = [];

        foreach ($books as $book) {
            $book['_id'] = (string) $book['_id'];
            $result [] = $book;
        }

        return $result;

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


}