<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
  微信网页授权的配置
*/
$config['wx_webauth_callback_url'] = urlencode("http://www.qdyljt.cn/mfdwechat/wxauth/index.php");
$config['wx_webauth_expire'] = 6600;
$config['wx_appID'] = 'wxc6052d889bb236c4';
$config['wx_appsecret'] = "39c2510583e07219c797125ee0591223";
