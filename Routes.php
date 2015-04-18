<?php

$app['router']->get('/', 'HomeController@getIndex');
$app['router']->get('notFound', 'HomeController@getNotFound');
$app['router']->get('shorten', 'HomeController@getShorten');
$app['router']->post('/create', 'HomeController@postIndex');
$app['router']->get('{id}', 'HomeController@goToLink');
