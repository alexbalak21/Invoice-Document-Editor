<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\DocumentService;

class DocumentController
{
    public function index(): void
    {
        Response::view('documents.index', ['title' => 'Documents']);
    }

    public function editor(): void
    {
        $request = new Request();
        $id      = $request->query('id') ? (int)$request->query('id') : null;
        $type    = strtoupper($request->query('type', 'INVOICE'));

        Response::view('documents.editor', [
            'title'   => $id ? "Edit Document #$id" : "New $type",
            'editId'  => $id,
            'newType' => $type,
        ]);
    }

    public function preview(): void
    {
        $request = new Request();
        $id      = (int)$request->query('id', 0);

        if (!$id) {
            http_response_code(400);
            echo '<p>Missing document ID</p>';
            return;
        }

        $service = new DocumentService();
        $doc     = $service->get($id);

        if (!$doc) {
            http_response_code(404);
            echo '<p>Document not found</p>';
            return;
        }

        Response::view('documents.preview', [
            'title' => ($doc->type . ' ' . $doc->number),
            'doc'   => $doc->data,
        ], layout: '');
    }

    public function documentation(): void
    {
        Response::view('documents.documentation', ['title' => 'Documentation']);
    }
}
