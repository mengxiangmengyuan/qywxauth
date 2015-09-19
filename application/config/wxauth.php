<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
  微信网页授权的配置
*/
$config['wx_webauth_callback_url'] = urlencode("http://www.qdyljt.cn/mfdwechat/qywxauth/index.php");
$config['wx_webauth_expire'] = 6600;
$config['wx_appID'] = 'wxa5228b44466e5642';
$config['wx_appsecret'] = "7ACqH1RU5ZDZCXU7MCh1bHfw5OSoo1Kcc8QNPjbCWsx8caGHp6i6P0wNcPiiAg7m";
