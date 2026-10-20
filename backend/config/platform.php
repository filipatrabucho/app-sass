<?php

return [
    /*
    | Domínio base dos tenants: cada empresa fica em {subdominio}.{tenant_base_domain}.
    */
    'tenant_base_domain' => env('TENANT_BASE_DOMAIN', 'nossaapp.localhost'),

    /*
    | Esquema usado para construir URLs do frontend (ex.: links de reposição de password).
    */
    'frontend_scheme' => env('FRONTEND_SCHEME', 'http'),

    /*
    | Porta do frontend quando não é a standard (dev). Vazio = sem porta.
    */
    'frontend_port' => env('FRONTEND_PORT'),

    /*
    | Tempo (segundos) de cache das licenças de módulos por tenant.
    */
    'licenses_cache_ttl' => (int) env('LICENSES_CACHE_TTL', 3600),

    /*
    | Primeiro administrador da plataforma (criado pelo seeder central).
    */
    'central_admin' => [
        'email' => env('CENTRAL_ADMIN_EMAIL', 'admin@nossaapp.pt'),
        'password' => env('CENTRAL_ADMIN_PASSWORD', 'password'),
    ],
];
