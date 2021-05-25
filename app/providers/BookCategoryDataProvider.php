<?php


namespace App\providers;
use App\util\BaseDataProvider;

class BookCategoryDataProvider extends BaseDataProvider
{

    protected function collection()
    {
        // TODO: Implement collection() method.
        return "book_categories";
    }

}