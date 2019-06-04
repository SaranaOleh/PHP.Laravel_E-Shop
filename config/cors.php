<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel CORS
    |--------------------------------------------------------------------------
    |
    | allowedOrigins, allowedHeaders and allowedMethods can be set to array('*')
    | to accept any value.
    |
    */
   
    'supportsCredentials' => false,
    'allowedOrigins' => ['*'],
    'allowedOriginsPatterns' => [],
    'allowedHeaders' => ['Origin', 'Content-Type', 'X-Auth-Token' , 'Authorization'],
    'allowedMethods' => ['GET', 'PATCH', 'POST', 'DELETE','OPTIONS'],
    'exposedHeaders' => [],
    'maxAge' => 0,

];
