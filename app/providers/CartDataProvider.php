<?php


namespace App\providers;


use App\util\BaseDataProvider;

class cartDataProvider extends BaseDataProvider
{
    protected function collection()
    {
        // TODO: Implement collection() method.
        return 'cart';
    }
}