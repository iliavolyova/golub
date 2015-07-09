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

Route::get('/login', function()
{
    if ( Input::has('code') )
    {
        $code = Input::get('code');

        // authenticate with Google API
        if ( Googlavel::authenticate($code) )
        {
            return Redirect::to('/protected');
        }
    }

    // get auth url
    $url = Googlavel::authUrl();

    return link_to($url, 'Login with Google!');
});

Route::get('/logout', function()
{
    // perform a logout with redirect
    return Googlavel::logout('/');
});

Route::get('/protected', function()
{
    // Get the google service (related scope must be set)
    $service = Googlavel::getService('Gmail');

    // invoke API call
    $params = array('q' => '-in:chats', 'maxResults' => '10');
    $msgList = $service->users_messages->listUsersMessages('me', $params)->getMessages();

    foreach ( $msgList as $msg )
    {
        $message = $service->users_messages->get('me', $msg->id)->getSnippet();
        echo "{$message} <br>";
    }

    return link_to('/logout', 'Logout');
});

// Homepage
Route::get('/', array('as' => 'home', 'uses' => 'HomeController@index'));
// Errors
Route::get('404', array('as' => '404', 'uses' => 'ErrorController@get404'));