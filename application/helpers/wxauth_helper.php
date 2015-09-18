<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// ------------------------------------------------------------------------

if ( ! function_exists('show_oauth_error'))
{
	function show_oauth_error($heading = '授权失败',$url='', $status_code = 500 )
	{
		$_error =& load_class('Exceptions', 'core');
		if(empty($url)){
			$CI =& get_instance();
			$url = $CI->config->item("wx_webauth_callback_url");
		}	
		echo $_error->show_error($heading, $url, 'error_oauth', $status_code);
		exit(1);
	}
}

