<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    'default' => env('LOG_CHANNEL', 'stack'),
    'network_enabled' => env('NETWORK_LOG_ENABLED', false),

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', (string) env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        // ====================================
        // 🔐 AUTHENTICATION LOGS
        // ====================================
        
        'auth' => [
            'driver' => 'daily',
            'path' => storage_path('logs/auth/auth.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 30,
            'permission' => 0664,
        ],

        'auth_errors' => [
            'driver' => 'daily',
            'path' => storage_path('logs/auth/errors.log'),
            'level' => 'error',
            'days' => 90,
            'permission' => 0664,
        ],

        'auth_network' => [
            'driver' => 'daily',
            'path' => env('NETWORK_LOG_PATH') . '\\auth\\auth.log',
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 90,
            'permission' => 0664,
        ],

        'auth_errors_network' => [
            'driver' => 'daily',
            'path' => env('NETWORK_LOG_PATH') . '\\auth\\errors.log',
            'level' => 'error',
            'days' => 365,
            'permission' => 0664,
        ],

        // ====================================
        // 📦 SALES ORDER LOGS
        // ====================================
        
        'sales_orders' => [
            'driver' => 'daily',
            'path' => storage_path('logs/sales_orders/operations.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 60,
            'permission' => 0664,
        ],

        'sales_orders_errors' => [
            'driver' => 'daily',
            'path' => storage_path('logs/sales_orders/errors.log'),
            'level' => 'error',
            'days' => 90,
            'permission' => 0664,
        ],

        'sales_orders_network' => [
            'driver' => 'daily',
            'path' => env('NETWORK_LOG_PATH') . '\\sales_orders\\operations.log',
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 180,
            'permission' => 0664,
        ],

        'sales_orders_errors_network' => [
            'driver' => 'daily',
            'path' => env('NETWORK_LOG_PATH') . '\\sales_orders\\errors.log',
            'level' => 'error',
            'days' => 365,
            'permission' => 0664,
        ],

        // ====================================
        // 🚚 DELIVERIES LOGS
        // ====================================
        
        'deliveries' => [
            'driver' => 'daily',
            'path' => storage_path('logs/deliveries/operations.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 60,
            'permission' => 0664,
        ],

        'deliveries_errors' => [
            'driver' => 'daily',
            'path' => storage_path('logs/deliveries/errors.log'),
            'level' => 'error',
            'days' => 90,
            'permission' => 0664,
        ],

        'deliveries_network' => [
            'driver' => 'daily',
            'path' => env('NETWORK_LOG_PATH') . '\\deliveries\\operations.log',
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 180,
            'permission' => 0664,
        ],

        'deliveries_errors_network' => [
            'driver' => 'daily',
            'path' => env('NETWORK_LOG_PATH') . '\\deliveries\\errors.log',
            'level' => 'error',
            'days' => 365,
            'permission' => 0664,
        ],

        // ====================================
        // 📊 OPERATIONS LOGS (General)
        // ====================================
        
        'operations' => [
            'driver' => 'daily',
            'path' => storage_path('logs/operations/general.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 30,
            'permission' => 0664,
        ],

        'operations_errors' => [
            'driver' => 'daily',
            'path' => storage_path('logs/operations/errors.log'),
            'level' => 'error',
            'days' => 90,
            'permission' => 0664,
        ],

        'operations_network' => [
            'driver' => 'daily',
            'path' => env('NETWORK_LOG_PATH') . '\\operations\\general.log',
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 90,
            'permission' => 0664,
        ],

        'operations_errors_network' => [
            'driver' => 'daily',
            'path' => env('NETWORK_LOG_PATH') . '\\operations\\errors.log',
            'level' => 'error',
            'days' => 365,
            'permission' => 0664,
        ],

        // ====================================
        // 👥 CUSTOMER LOGS
        // ====================================
        
        'customers' => [
            'driver' => 'daily',
            'path' => storage_path('logs/customers/operations.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 60,
            'permission' => 0664,
        ],

        'customers_network' => [
            'driver' => 'daily',
            'path' => env('NETWORK_LOG_PATH') . '\\customers\\operations.log',
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 180,
            'permission' => 0664,
        ],

        // ====================================
        // 📦 ITEMS LOGS
        // ====================================
        
        'items' => [
            'driver' => 'daily',
            'path' => storage_path('logs/items/operations.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 60,
            'permission' => 0664,
        ],

        'items_network' => [
            'driver' => 'daily',
            'path' => env('NETWORK_LOG_PATH') . '\\items\\operations.log',
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 180,
            'permission' => 0664,
        ],

        // ====================================
        // 💰 ACCOUNTING LOGS
        // ====================================
        
        'accounting' => [
            'driver' => 'daily',
            'path' => storage_path('logs/accounting/operations.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 90,
            'permission' => 0664,
        ],

        'accounting_errors' => [
            'driver' => 'daily',
            'path' => storage_path('logs/accounting/errors.log'),
            'level' => 'error',
            'days' => 180,
            'permission' => 0664,
        ],

        'accounting_network' => [
            'driver' => 'daily',
            'path' => env('NETWORK_LOG_PATH') . '\\accounting\\operations.log',
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 365,
            'permission' => 0664,
        ],

        'accounting_errors_network' => [
            'driver' => 'daily',
            'path' => env('NETWORK_LOG_PATH') . '\\accounting\\errors.log',
            'level' => 'error',
            'days' => 730, // 2 years for accounting
            'permission' => 0664,
        ],

        // ====================================
        // 🔧 SYSTEM LOGS
        // ====================================
        
        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', 'Laravel Log'),
            'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'handler_with' => [
                'stream' => 'php://stderr',
            ],
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],
    ],
];