<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'FinanceFlow API',
    description: 'FinanceFlow API Documentation'
)]
#[OA\Server(
    url: 'http://localhost:8000',
    description: 'Local server development (localhost)'
)]
#[OA\Server(
    url: 'http://127.0.0.1:8000',
    description: 'Local server development (127.0.0.1)'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    name: 'Authorization',
    in: 'header',
    bearerFormat: 'JWT',
    scheme: 'bearer'
)]
abstract class Controller
{
    // AuthorizesRequests — without it, $this->authorize() does not work
    use AuthorizesRequests;
}
