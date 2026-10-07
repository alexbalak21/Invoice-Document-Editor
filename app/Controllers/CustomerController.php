<?php

namespace App\Controllers;

use App\Core\Response;

class CustomerController
{
    public function index(): void
    {
        Response::view('customers.index', ['title' => 'Customers']);
    }
}
