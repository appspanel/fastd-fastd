<?php

use Middleware\FooMiddleware;
use Middleware\HttpAuthenticationMiddleware;

route()->get('/', 'IndexController@welcome');

route()->get('/foo/{name}', 'IndexController@sayHello');

route()
    ->post('/foo/{name}', 'IndexController@middleware')
    ->withAddMiddleware(new FooMiddleware())
;

route()->get('/db', 'IndexController@db');

route()->get('/model', 'IndexController@model');

route()
    ->get('/auth', 'IndexController@auth')
    ->withAddMiddleware(new HttpAuthenticationMiddleware())
;
