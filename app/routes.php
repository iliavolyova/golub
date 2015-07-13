<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/


Route::get('/logout', function()
{
    // perform a logout with redirect
    return Googlavel::logout('/');
});

// Login/Landing page
Route::get('/', array('as' => 'login', 'uses' => 'LoginController@login'));

// Inbox
Route::get('/inbox', array('as' => 'inbox', 'uses' => 'HomeController@inbox'));


// Errors
Route::get('404', array('as' => '404', 'uses' => 'ErrorController@get404'));