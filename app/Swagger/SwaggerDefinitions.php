<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

/**
 * Velora API - Documentación Swagger/OpenAPI
 */
#[OA\Info(
    title: 'Velora API',
    version: '1.0.0',
    description: 'API de autenticación para Velora',
    contact: new OA\Contact(
        email: 'support@velora.com'
    )
)]
#[OA\Server(
    url: 'http://velora.test/api',
    description: 'Servidor local'
)]
#[OA\SecurityScheme(
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    securityScheme: 'bearerAuth'
)]
class SwaggerDefinitions
{
}
