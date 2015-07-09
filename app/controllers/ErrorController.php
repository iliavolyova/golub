<?php

class ErrorController extends BaseController {
    /**
     * Layout
     *
     * @var string
     */
    public $layout = 'layouts.error';
    /**
     * Display the 404 page not found
     */
    public function get404()
    {
        $this->layout->head->title = 404;
        $this->layout->content = View::make('error/404');

    }
}