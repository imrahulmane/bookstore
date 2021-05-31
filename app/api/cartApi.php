<?php

use App\controllers\cartController;
use Slim\App;

$configuration = [
    'settings' => [
        'displayErrorDetails' => true,
    ],
];
$app = new App($configuration);

$app->post('/cart', function($request, $response){
    $data = $request->getParam('data');
    $data = json_decode($data, true);
    $cartController = new cartController();
    $result = $cartController->addtoCart($data);
    return $response->withJson($result);
});

$app->post('/cart/item/{cart_id}', function ($request, $response){
   $cartId = $request->getAttribute('cart_id');
   $data = $request->getParam('data');
   $data = json_decode($data, 1);
   $cartController = new cartController();
   $result = $cartController->addItemToCart($cartId, $data);
   return $response->withJson($result);
});

$app->get('/cart/{id}', function($request, $response){
    $id = $request->getAttribute('id');
    $cartController = new cartController();
    $result = $cartController->getCartDetails($id);
    return $response->withJson($result);
});

$app->get('/cart', function($request, $response){
    $cartController = new cartController();
    $result = $cartController->getAllCarts();
    return $response->withJson($result);
});

$app->post('/cart/complete/{cart_id}', function($request, $response){
    $id = $request->getAttribute('cart_id');
    $cartController = new cartController();
    $result = $cartController->completeOrder($id);
    return $response->withJson($result);
});


$app->delete('/cart/remove/{cart_id}', function($request, $response){
    $id = $request->getAttribute('cart_id');
    $cartController = new cartController();
    $result = $cartController->removeCart($id);
    return $response->withJson($result);
});

$app->delete('/cart/removeOne/{cart_id}', function($request, $response){
    $id = $request->getAttribute('cart_id');
    $item_id = $request->getParam('item_id');
    $cartController = new cartController();
    $result = $cartController->removeItemFromCart($id, $item_id);
    return $response->withJson($result);
});



$app->run();