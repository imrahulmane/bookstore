<?php


namespace App\controllers;


use App\providers\BookDataProvider;
use App\providers\CartDataProvider;
use App\util\BaseDataProvider;
use MongoDB\BSON\ObjectId;

class cartController
{
    public function addtoCart($data) {
        $data['status'] = 'active'; //add status as active
        foreach ($data['orders'] as $key => $order) {
            $latestPrice = $this->calculatePrice($order['book_id']);
            $orderPrice =  $this->calculateOrderPrice($latestPrice, (int) $order['qty']);
            $data['orders'][$key]['price'] = $latestPrice;
            $data['orders'][$key]['order_price'] = $orderPrice;
            $data['orders'][$key]['book_status'] = false; //set book_status as false initially

            $isQuantityAvailable = $this->checkQuantity($order['book_id'], $order['qty']); //check quantity available

            if($isQuantityAvailable) {
                $data['orders'][$key]['book_status'] = true;
                $data['total_price'] += $data['orders'][$key]['order_price'];
            }
        }

        $cartDataProvider = new CartDataProvider();
        return $cartDataProvider->insertOne($data);

    }

    public function getCartDetails($id) {
        $searchArray = ['_id' => new ObjectId($id)];
        $cartDataProvider = new CartDataProvider();
        $result = $cartDataProvider->findOne($searchArray);

        if(!$result) {
            return [
                "status" => "failed",
                "message" => "There is no such cart with provided ID"
            ];
        }

        return $result;
    }


    public function getAllCarts() {
        $cartDataProvider = new CartDataProvider();
        $result = $cartDataProvider->find();

        if(!$result) {
            return [
                "status" => "failed",
                "message" => "There are no carts available"
            ];
        }

        return $result;
    }

    public function completeOrder($id)
    {
        $searchArray = ['_id' => new ObjectId($id)];
        $cartDataProvider = new CartDataProvider();
        $cart = $cartDataProvider->findOne($searchArray);

        $result = $this->processOrderData($cart);

        $this->updateBookQuantity($result['orders']);

        $cartDataProvider = new CartDataProvider();
        $searchArray = ['_id' => $result['_id']];
        unset($result['_id']);
        $cartDataProvider->replaceOne($searchArray, $result);

        return [
            'status' => 'Success',
            'message' => 'Your order is completed'
        ];
    }

    public function removeCart($id)
    {
        $cartDataProvider = new CartDataProvider();
        $searchArray = ['_id' => new ObjectId($id)];
        $result = $cartDataProvider->deleteOne($searchArray);
        return $result;
    }

    public function removeItemFromCart($cart_id, $item_id) {
        $cartDataProvider = new CartDataProvider();
        $searchArray = ['_id' => new ObjectId($cart_id)];
        $updateArray = ['$pull' => ['orders' => ['book_id' => $item_id]]];
        return $cartDataProvider->updateOne($searchArray, $updateArray);
    }

    public function addItemToCart($cart_id, $data) {

        $cartDataProvider = new CartDataProvider();
        $searchArray = ['_id' => new ObjectId($cart_id)];
        $cart = $cartDataProvider->findOne($searchArray);

        $price = $this->calculatePrice($data['book_id']);
        $orderPrice = $price * $data['qty'];
        $bookStatus = $this->checkQuantity($data['book_id'], $data['qty']);



        if($bookStatus) {
            $cart['total_price'] += $orderPrice;
        }

        $processData = [
            'book_id' => $data['book_id'],
            'qty' => $data['qty'],
            'price' => $price,
            'order_price' =>$orderPrice,
            'book_status' => $bookStatus
        ];

        $bulk = [];
        $updateArray = ['$push' => ['orders' => $processData]];
        $updatePrice = ['$set' => ['total_price' => $cart['total_price']]];
        $bulkUpdateArray = $cartDataProvider->bulkUpdate($searchArray, $updateArray);
        $bulkUpdatePrice = $cartDataProvider->bulkUpdate($searchArray, $updatePrice);
        array_push($bulk, $bulkUpdateArray, $bulkUpdatePrice);

        return  $cartDataProvider->bulkWrite($bulk, true);

    }

    private function processOrderData($cart) {
        $cart['total_price'] = 0;
        foreach ($cart['orders'] as $key => $item) {
            $isQuantityAvailabel = $this->checkQuantity($item['book_id'], $item['qty']);

            $bookId = $item['book_id'];
            $latestPrice = $this->calculatePrice($bookId);

            $cart['orders'][$key]['order_price'] = $latestPrice * $item['qty'];

            if($isQuantityAvailabel) {
                $cart['orders'][$key]['book_status'] = true;
                $cart['total_price'] += $cart['orders'][$key]['order_price'];
            } else {
                $cart['orders'][$key]['book_status'] = false;
            }
        }

        $cart['status'] = 'completed';
        return $cart;
    }

    private function calculatePrice($bookId){
        $bookDataProvider = new BookDataProvider();
        $searchArray = ['_id' => new ObjectId($bookId)];
        $bookDetails = $bookDataProvider->findOne($searchArray);
        $seasonStartDate =  $bookDetails['season_start_date'];
        $seasonEndDate =  $bookDetails['season_end_date'];
        $currentDate = date('Y-m-d H:i:s', time());

        $isSeason = $this->isSeason($seasonStartDate, $seasonEndDate, $currentDate);

        if($isSeason) {
            return $bookDetails['season_price'];
        }

        return $bookDetails['price'];
    }

    private function isSeason($startDate, $endDate, $currentDate) {
        $startDate = strtotime($startDate);
        $endDate = strtotime($endDate);
        $currentDate = strtotime($currentDate);
        return (($currentDate >= $startDate) && ($currentDate <= $endDate));
    }

    public function updateBookQuantity($cart){
        $bookDataProvider = new BookDataProvider();
        $bookIds = [];

        foreach ($cart as $item) {
            if(!$item['book_status']) {
                continue;
            }

            $bookIds [] = new ObjectId($item['book_id']);
        }

        $projection = ['quantity' => 1];
        $searchArray = ['_id' => ['$in' => $bookIds]];
        $books = $bookDataProvider->find($searchArray, $projection);
        $bookIdAndQuantityMap = [];

        foreach ($books as $book) {
            $bookIdAndQuantityMap[ (string) $book['_id']] = $book;
        }

        $bulk = [];
        foreach ($cart as $item) {
            $bookId = $bookIdAndQuantityMap[$item['book_id']]['_id'];
            $searchArray = ['_id' => $bookId];
            $updatedQuantity = $bookIdAndQuantityMap[$item['book_id']]['quantity'] - $item['qty'];
            $updateArray = [ '$set' => ['quantity' => $updatedQuantity]];
            $bulk [] = $bookDataProvider->bulkUpdate($searchArray, $updateArray);
        }

        if(!empty($bulk)) {
            $bookDataProvider->bulkWrite($bulk, true);
        }
    }

    private function checkQuantity($book_id, $quantity) {
        $searchArray = ['_id' => new ObjectId($book_id)];
        $booksDataProvider = new BookDataProvider();
        $result = $booksDataProvider->findOne($searchArray);
        if($result['quantity'] < $quantity) {
            return false;
        }
        return true;
    }

    private function calculateOrderPrice($price, $quantity) {
        return $price * (int) $quantity;
    }

}