<?php

declare(strict_types=1);

/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\MindMap\Controller\PageController->index()
 *
 * The controller class has to be registered in lib/AppInfo/Application.php
 */
return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
    ],
    'ocs' => [
        // Mind map API
        ['name' => 'api#getMindMaps', 'url' => '/api/mindmaps', 'verb' => 'GET'],
        ['name' => 'api#getMindMap', 'url' => '/api/mindmaps/{name}', 'verb' => 'GET'],
        ['name' => 'api#saveMindMap', 'url' => '/api/mindmaps/{name}', 'verb' => 'POST'],
        ['name' => 'api#deleteMindMap', 'url' => '/api/mindmaps/{name}', 'verb' => 'DELETE'],
        ['name' => 'api#getSocketInfo', 'url' => '/api/mindmaps/{name}/socket', 'verb' => 'GET'],
    ]
]; 