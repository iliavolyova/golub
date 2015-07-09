<?php

class HomeController extends BaseController {

    /**
     * Welcome
     */
    public function index()
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

        $this->layout->content = View::make('home.index')->with('googleauth', $url);
    }
}