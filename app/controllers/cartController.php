<?php


namespace App\controllers;


use App\providers\BookDataProvider;
use App\providers\CartDataProvider;
use MongoDB\BSON\ObjectId;

class cartController
{
    public function addtoCart($data) {
        $data['status'] = 'active'; //add status as active

        foreach ($data['orders'] as $key => $order) {
            $latestPrice = $this->getPrice($order['book_id']);
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

    public function completeOrder($id, $proceed)
    {
        $searchArray = ['_id' => new ObjectId($id)];
        $cartDataProvider = new CartDataProvider();
        $cart = $cartDataProvider->findOne($searchArray);

        $result = $this->processOrderData($cart, $proceed);

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

    private function processOrderData($cart, $proceed) {

        $cart['total_price'] = 0;
        foreach ($cart['orders'] as $key => $item) {
            $isQuantityAvailabel = $this->checkQuantity($item['book_id'], $item['qty']);
            $changedPrice = $this->checkPriceChanged($item['book_id'], $item['price']);

            if($changedPrice !== false) {
                $latestOrderPrice = $this->calculateOrderPrice($changedPrice, $item['qty']);
                $cart['orders'][$key]['price'] = $changedPrice;
                $cart['orders'][$key]['order_price'] = $latestOrderPrice;
            }

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

    private function checkPriceChanged($book_id, $price){
        $searchArray = ['_id' => new ObjectId($book_id)];
        $booksDataProvider = new BookDataProvider();
        $result = $booksDataProvider->findOne($searchArray);

        if((int) $result['price'] !== $price){
            return $result['price'];
        }
        return false;
    }

    private function getPrice($book_id) {
        $searchArray = ['_id' => new ObjectId($book_id)];
        $booksDataProvider = new BookDataProvider();
        $result = $booksDataProvider->findOne($searchArray);
        return (int) $result['price'];
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