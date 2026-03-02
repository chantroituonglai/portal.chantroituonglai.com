<?php

defined('BASEPATH') or exit('No direct script access allowed');
header('Access-Control-Allow-Origin: *');

class Api_v2 extends ClientsController
{
   
    public function index()
    {
       echo 'Hi';
    }
}
