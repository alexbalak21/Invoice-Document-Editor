<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\ItemService;

class ItemApiController
{
    private ItemService $service;
    private Request $request;

    public function __construct()
    {
        $this->service = new ItemService();
        $this->request = new Request();
    }

    public function index(): void
    {
        Response::ok(['items' => $this->service->list($this->request->query('q', ''))]);
    }

    public function show(array $params): void
    {
        $item = $this->service->get((int)$params['id']);
        if (!$item) Response::error('Item not found', 404);
        Response::ok(['item' => $item->toArray()]);
    }

    public function store(): void
    {
        try {
            $id = $this->service->create($this->request->all());
            Response::ok(['id' => $id]);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage());
        }
    }

    public function update(array $params): void
    {
        try {
            $this->service->update((int)$params['id'], $this->request->all());
            Response::ok();
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage());
        }
    }

    public function destroy(array $params): void
    {
        $this->service->delete((int)$params['id']);
        Response::ok();
    }
}
