<?php

defined('DS') or exit('No direct script access.');

class Base_Controller extends Controller
{
    /**
     * Handler untuk pemanggilan method yang tidak ada.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return Response
     */
    public function __call($method, $parameters)
    {
        return Response::error('404');
    }
}
