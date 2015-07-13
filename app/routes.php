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
Route::get('/', array('as' => 'login', 'uses' => 'LoginController@login'));

Route::get('/lang/{lang}', function($lang)
{
    Session::put('my.locale', $lang);
    return Redirect::back();
});


Route::get('/inbox', array('as' => 'inbox', 'uses' => 'HomeController@inbox'));
Route::get('/outbox', array('as' => 'inbox', 'uses' => 'HomeController@outbox'));
Route::get('/favorites', array('as' => 'inbox', 'uses' => 'HomeController@favorites'));

Route::post('/sendmail', array('as' => 'sendmail', 'uses' => 'HomeController@sendmail'));
Route::post('/fav', array('as' => 'fav', 'uses' => 'HomeController@setfavorite'));

Route::get('404', array('as' => '404', 'uses' => 'ErrorController@get404'));