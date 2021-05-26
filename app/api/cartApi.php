<?php

use App\controllers\cartController;
use Slim\App;

$configuration = [
    'settings' => [
        'displayErrorDetails' => true,
    ],
];
$app = new App($configuration);

$app->post('/addtoCart', function($request, $response){
    $data = $request->getParam('data');
    $cartController = new cartController();
    $result = $cartController->addtoCart($data);
    return $response->withJson($result);
});

$app->get('/cartDetails/{id}', function($request, $response){
    $id = $request->getAttribute('id');
    $cartController = new cartController();
    $result = $cartController->getCartDetails($id);
    return $response->withJson($result);
});

$app->get('/getAllCarts', function($request, $response){
    $cartController = new cartController();
    $result = $cartController->getAllCarts();
    return $response->withJson($result);
});

$app->post('/completeOrder', function($request, $response){
    $data = $request->getParam('data');
    $cartController = new cartController();
    $result = $cartController->completeOrder($data);
    return $response->withJson($result);
});

//$app->post('/addtoCart', function($request, $response){});

$app->run();