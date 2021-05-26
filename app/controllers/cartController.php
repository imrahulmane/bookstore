<?php


namespace App\controllers;


use App\providers\BookDataProvider;
use App\providers\CartDataProvider;
use MongoDB\BSON\ObjectId;

class cartController
{
    public function addtoCart($data) {
        $data = json_decode($data, true);

        $data['total_price'] = 0;  //add total_price
        $data['status'] = 'active'; //add status as active

        foreach ($data['orders'] as $key => $order) {
            $data['orders'][$key]['price'] = $this->getPrice($order['book_id']);  //get price from db
            $data['orders'][$key]['book_status'] = false; //set book_status as false initially
            $data['orders'][$key]['order_price'] = $data['orders'][$key]['price'] * (int) $data['orders'][$key]['qty'];

            $isQuantityAvailable = $this->checkQuantity($data['orders'][$key]['book_id'], $data['orders'][$key]['qty']); //check quantity available

            if($isQuantityAvailable) {
                $data['orders'][$key]['book_status'] = true;
            }

            if($data['orders'][$key]['book_status']) {
                $data['total_price'] += $data['orders'][$key]['order_price'];
            }
        }

        $cartDataProvider = new CartDataProvider();
        $result = $cartDataProvider->insertOne($data);
        return $result;

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

    public function completeOrder($data)
    {
       $data = json_decode($data, true);
        $this->processOrderData($data);

    }

    private function processOrderData($data) {
        $cart = $this->getCartDetails($data['id']);

        foreach ($cart['orders'] as $key => $cartItem) {

            if(!$cart['orders'][$key]['book_status']) {
                $isQuantityAvailable = $this->checkQuantity($cart['orders'][$key]['book_id'], $cart['orders'][$key]['qty']);
                if ($isQuantityAvailable) {
                    $cart['orders'][$key]['book_status'] = true;
                    $cart['total_price'] += $cart['orders'][$key]['order_price'];
                }
            }

            $isPriceChanged = $this->checkPriceChanged($cart['orders'][$key]['book_id'], $cart['orders'][$key]['price']);

            if($isPriceChanged) {

                $cart['total_price'] -= $cart['orders'][$key]['order_price'];
                $cart['orders'][$key]['price'] = $this->getPrice($cart['orders'][$key]['book_id']);
                $cart['orders'][$key]['order_price'] = $cart['orders'][$key]['price'] * $cart['orders'][$key]['qty'];
                $cart['total_price'] += $cart['orders'][$key]['order_price'];
            }
        }

        print_r($cart);
        exit();
    }


    private function checkPriceChanged($book_id, $price){
        $searchArray = ['_id' => new ObjectId($book_id)];
        $booksDataProvider = new BookDataProvider();
        $result = $booksDataProvider->findOne($searchArray);

        if((int) $result['price'] !== $price){
            return true;
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

        if((int) $result['quantity'] < $quantity) {
            return false;
        }
        return true;
    }


}