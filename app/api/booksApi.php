<?php

use App\controllers\BooksController;
use Slim\App;

$configuration = [
    'settings' => [
        'displayErrorDetails' => true,
    ],
];
$app = new App($configuration);

$app->post('/addBook', function($request, $response){
    $parsedBody = $request->getParsedBody();
    $booksController = new BooksController();
    $result = $booksController->addBook($parsedBody);
    return $response->withJson($result);
});

$app->put('/updateBook/{id}', function($request, $response){
    $id = $request->getAttribute('id');
    $parsedBody = $request->getParsedBody();
    $booksController = new BooksController();
    $result = $booksController->updateBook($id, $parsedBody);
    return $response->withJson($result);
});

$app->get('/bookDetails/{id}', function($request, $response){
    $id = $request->getAttribute('id');
    $booksController = new BooksController();
    $result = $booksController->getBookDetails($id);
    return $response->withJson($result);
});

$app->get('/getAllBooks', function($request, $response){
    $booksController = new BooksController();
    $result = $booksController->getAllBooks();
    return $response->withJson($result);
});

$app->delete('/deleteBook/{id}', function($request, $response){
    $id = $request->getAttribute('id');
    $booksController = new BooksController();
    $result = $booksController->deleteBook($id);
    return $response->withJson($result);
});


$app->run();