<?php


namespace App\providers;


use App\util\BaseDataProvider;

class BookDataProvider extends BaseDataProvider
{

    protected function collection()
    {
        // TODO: Implement collection() method.
        return "books";
    }
}