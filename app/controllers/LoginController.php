<?php

class LoginController extends BaseController{

    /**
     * Login
     */
    public function login()
    {
        if ( Input::has('code') )
        {
            $code = Input::get('code');

            // authenticate with Google API
            if ( Googlavel::authenticate($code) )
            {
                return Redirect::to('/inbox');
            }
        }

        // get auth url
        $url = Googlavel::authUrl();
        return View::make('login.login')->with('googleauth', $url);
    }
}