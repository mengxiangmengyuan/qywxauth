<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Test extends CI_Controller {
	
	public function index() {
		header ( "Content-Type: text/html; charset=UTF-8" );
		
		$myssin = array (
				"__ci_last_regenerate" => 1442198788,
				"access_token" => "OezXcEiiBSKSxW0eoylIeEg9G0VtlBYBKvmCKw6_LfUhN0Jw3UyaRI1GYrJYsRKaF-RB8wr8i8Tk2Iocek1Q1Bs99Kj8f2zUS23WO58oVFeYzJi7b7c0nTrvPYRsp4JGV_csOn-k1IkYLhBvm_Yk_g",
				"refresh_token" => "OezXcEiiBSKSxW0eoylIeEg9G0VtlBYBKvmCKw6_LfUhN0Jw3UyaRI1GYrJYsRKabqTQDnAyPz2f4dGthuaI0WwtnZsLIQYBuj7cxVZKV9ZMXwyvG2yKfy8d-B5LN2jhZdbTq_za7Veueqf4N37hqw",
				"openid" => "ov2bbvrOCW-cnVfCgZMbGj2AT9cM",
				"scope" => "snsapi_userinfo",
				"expires_in" => 7200,
				"openidtime" => 1442198793,
				"nickname" => "MENG" 
		);
		
		//var_dump( $myssin);
		
		$this->load->model('user');
		
		$this->user->adduser($myssin);
	}
	
	
}
