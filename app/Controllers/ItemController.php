<?php

namespace App\Controllers;

use App\Core\Response;

class ItemController
{
    public function index(): void
    {
        Response::view('items.index', ['title' => 'Items & Services']);
    }
}
