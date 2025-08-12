<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Controls the HTTP route that your GraphQL server responds to.
    | You may set `route` => false, to disable the default route
    | registration and take full control.
    |
    */

    'route' => [
        /*
         * The URI the endpoint responds to, e.g. mydomain.com/graphql.
         */
        'uri' => '/graphql',

        /*
         * Lighthouse creates a named route for convenient URL generation and redirects.
         */
        'name' => 'graphql',

        /*
         * Beware that middleware defined here runs before the GraphQL execution phase,
         * make sure to return spec-compliant responses in case an error is thrown.
         */
        'middleware' => [
            \Nuwave\Lighthouse\Http\Middleware\AcceptJson::class,

            // Logs in a user if they are authenticated. In contrast to Laravel's 'auth'
            // middleware, this delegates auth and permission checks to the field level.
            \Nuwave\Lighthouse\Http\Middleware\AttemptAuthentication::class,

            // Logs every incoming GraphQL query.
            // \Nuwave\Lighthouse\Http\Middleware\LogGraphQLQueries::class,
        ],

        /*
         * The `prefix` and `domain` configuration options are optional.
         */
        // 'prefix' => '',
        // 'domain' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guard
    |--------------------------------------------------------------------------
    |
    | This configuration option allows you to use a different guard for GraphQL
    | requests than what's defined in your config/auth.php file.
    | You may omit this configuration if you want to use the default guard.
    |
    */

    'guard' => 'api',

    /*
    |--------------------------------------------------------------------------
    | Schema Location
    |--------------------------------------------------------------------------
    |
    | Path to your .graphql schema file.
    | Additional schema files may be imported from within that file.
    |
    */

    'schema_path' => base_path('graphql/schema.graphql'),

    /*
    |--------------------------------------------------------------------------
    | Schema Cache
    |--------------------------------------------------------------------------
    |
    | A large part of schema generation consists of parsing and AST manipulation.
    | This operation is very expensive, so it is highly recommended enabling
    | caching in production.
    |
    */

    'cache' => [
        /*
         * Setting to true enables schema caching.
         */
        'enable' => env('LIGHTHOUSE_CACHE_ENABLE', env('APP_ENV') !== 'local'),

        /*
         * Allowed values:
         * - null: use the default cache store
         * - string: the name of the cache store as defined in config/cache.php
         * - \Illuminate\Contracts\Cache\Repository: cache store instance
         */
        'store' => env('LIGHTHOUSE_CACHE_STORE', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Namespaces
    |--------------------------------------------------------------------------
    |
    | These are the default namespaces where Lighthouse looks for classes to
    | extend functionality of the schema. You may pass in either a string
    | or an array, they are tried in order and the first match is used.
    |
    */

    'namespaces' => [
        'models' => ['App\\Models'],
        'queries' => ['App\\GraphQL\\Queries'],
        'mutations' => ['App\\GraphQL\\Mutations'],
        'subscriptions' => ['App\\GraphQL\\Subscriptions'],
        'interfaces' => ['App\\GraphQL\\Interfaces'],
        'unions' => ['App\\GraphQL\\Unions'],
        'scalars' => ['App\\GraphQL\\Scalars'],
        'directives' => ['App\\GraphQL\\Directives'],
        'validators' => ['App\\GraphQL\\Validators'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Control how Lighthouse handles security related query validation.
    | Read more at https://lighthouse-php.com/master/security/security.html
    |
    */

    'security' => [
        'max_query_complexity' => 1000,
        'max_query_depth' => 15,
        'disable_introspection' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Limits on pagination queries.
    |
    */

    'pagination' => [
        /*
         * Allow clients to query paginated lists without specifying the amount of items.
         * Setting this to `null` means clients have to explicitly ask for the count.
         */
        'default_count' => 25,

        /*
         * Limit the maximum amount of items that clients can request from paginated lists.
         * Setting this to `null` means the count is unrestricted.
         */
        'max_count' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug
    |--------------------------------------------------------------------------
    |
    | Control the debug level as described in https://webonyx.github.io/graphql-php/error-handling/
    | Debugging is only applied if the global Laravel debug config is set to true.
    |
    | When you set this value through an environment variable, use the following reference table:
    |  0 => INCLUDE_NONE
    |  1 => INCLUDE_DEBUG_MESSAGE
    |  2 => INCLUDE_TRACE
    |  4 => RETHROW_INTERNAL_EXCEPTIONS
    |  8 => RETHROW_UNSAFE_EXCEPTIONS
    |
    | For example:
    | - To include debug messages and traces, set it to `3` (1 + 2)
    | - To include debug messages and rethrow unsafe exceptions, set it to `9` (1 + 8)
    |
    */

    'debug' => env('LIGHTHOUSE_DEBUG', 3), // INCLUDE_DEBUG_MESSAGE | INCLUDE_TRACE

    /*
    |--------------------------------------------------------------------------
    | Error Handlers
    |--------------------------------------------------------------------------
    |
    | Register error handlers that receive the Errors that occur during execution and
    | handle them. You may use this to log, filter or format the errors.
    | The classes must implement \Nuwave\Lighthouse\Execution\ErrorHandler
    |
    */

    'error_handlers' => [
        \Nuwave\Lighthouse\Execution\AuthenticationErrorHandler::class,
        \Nuwave\Lighthouse\Execution\AuthorizationErrorHandler::class,
        \Nuwave\Lighthouse\Execution\ValidationErrorHandler::class,
        // \Nuwave\Lighthouse\Execution\ExtensionErrorHandler::class,
        \Nuwave\Lighthouse\Execution\ReportingErrorHandler::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Extensions
    |--------------------------------------------------------------------------
    |
    | Register extension classes that implement \Nuwave\Lighthouse\Support\Contracts\GraphQLContext
    |
    */

    'extensions' => [
        // \Nuwave\Lighthouse\Extensions\ExtensionRequest::class,
        // \Nuwave\Lighthouse\CacheControl\CacheControlExtension::class,
        // \Nuwave\Lighthouse\Pagination\PaginationServiceProvider::class,
        // \Nuwave\Lighthouse\OrderBy\OrderByServiceProvider::class,
        // \Nuwave\Lighthouse\WhereConditions\WhereConditionsServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscriptions
    |--------------------------------------------------------------------------
    |
    | Lighthouse can act as a WebSocket server for Subscriptions. This feature
    | requires the pusher/pusher-php-server package.
    |
    */

    'subscriptions' => [
        /*
         * Determines if Lighthouse should serve subscriptions.
         */
        'storage' => env('LIGHTHOUSE_SUBSCRIPTION_STORAGE', 'redis'),

        /*
         * Any extra configuration Lighthouse should know about subscriptions goes here.
         */
        'storage_ttl' => env('LIGHTHOUSE_SUBSCRIPTION_STORAGE_TTL', null),

        /*
         * Determines the queue to push broadcasted subscription data.
         */
        'queue_broadcasts' => env('LIGHTHOUSE_QUEUE_BROADCASTS', true),

        /*
         * Determines the queue to push subscription data.
         */
        'broadcasts_queue_name' => env('LIGHTHOUSE_BROADCASTS_QUEUE_NAME', null),

        /*
         * Should the subscriptions extension be excluded when the environment is not local?
         */
        'exclude_empty' => env('LIGHTHOUSE_SUBSCRIPTION_EXCLUDE_EMPTY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Defer
    |--------------------------------------------------------------------------
    |
    | Configuration for the experimental @defer directive.
    |
    */

    'defer' => [
        /*
         * Maximum nested fields allowed in a single query.
         */
        'max_nested_fields' => 0,

        /*
         * Maximum execution time allowed for deferred queries (in seconds).
         */
        'max_execution_time' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Federation
    |--------------------------------------------------------------------------
    |
    | Lighthouse can act as a federated service.
    | https://www.apollographql.com/docs/federation/
    |
    */

    'federation' => [
        /*
         * Location of resolver classes when resolving the `_entities` field.
         */
        'entities_resolver_namespace' => 'App\\GraphQL\\Entities',
    ],
];
