<?php


use App\controllers\CategoryController;

$app = new \Slim\App();

$app->post('/addCategory', function($request, $response){
    $parsedBody = $request->getParsedBody();
    $categoryController = new CategoryController();
    $result = $categoryController->createCategory($parsedBody);
    return $response->withStatus(200)->withJson($result);
});

$app->run();