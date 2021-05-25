<?php


use App\controllers\CategoryController;
use Slim\App;

$configuration = [
    'settings' => [
        'displayErrorDetails' => true,
    ],
];
$app = new App($configuration);

$app->post('/addCategory', function($request, $response){
    $parsedBody = $request->getParsedBody();
    $categoryController = new CategoryController();
    $result = $categoryController->createCategory($parsedBody);
    return $response->withStatus(200)->withJson($result);
});

$app->put('/updateCategory/{id}', function ($request, $response){
    $id = $request->getAttribute('id');
    $parsedBody = $request->getParsedBody();
    $categoryController = new CategoryController();
    $result = $categoryController->updateCategory($id, $parsedBody);
    return $response->withStatus(200)->withJson($result);
});

$app->get('/getCategory/{id}', function ($request, $response){
    $id = $request->getAttribute('id');
    $categoryController = new CategoryController();
    $result = $categoryController->getCategory($id);
    return $response->withJson($result);
});

$app->get('/getCategories', function ($request, $response){
    $categoryController = new CategoryController();
    $result = $categoryController->getAllCategories();
    return $response->withJson($result);
});

$app->delete('/deleteCategory/{id}', function ($request, $response) {
    $id = $request->getAttribute('id');
    $moveId = $request->getParam('moveId');
    $categoryController = new CategoryController();
    $result = $categoryController->deleteCategory($id, $moveId);
    return $response->withJson($result);
});

$app->run();