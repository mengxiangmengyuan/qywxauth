<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends MY_Controller {


	public function index()
	{
	   header("Content-Type: text/html; charset=UTF-8");  
	  //var_dump($this->wxGetToken());      
      // var_dump($_SESSION);
	   echo '欢迎，'.$_SESSION['UserId'];
	}
}
