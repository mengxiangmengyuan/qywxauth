<?php

/**
 * 微信网页登录授权
 * @masofeng
 * @0.1
 */

defined ( 'BASEPATH' ) or exit ( 'No direct script access allowed' );

class MY_Controller extends CI_Controller {
	
	public function __construct() {
		parent::__construct ();
 		$this->wxOauth();
		$this->load->model('user');
		$this->user->adduser($_SESSION);
	}
	
	/*
	 *企业微信号 OAuth2.0受权
	* http://qydev.weixin.qq.com/wiki/index.php?title=OAuth验证接口
	* 共有3个步骤：1、企业获取code；2、获取access_token；3、通过code和access_token获取成员信息
	* 如果连接参数中带有code参数,说明是由第一步成功返回，获取access_token，通过code和access_token获取成员信息，将用户信息存入session，以免对同一用户反复要求受权
	* 如果连接参数中不带有code参数，说明不是来自微信OAuth2.0受权返回的页面
	* 判断session的信息是否过期，如果没有信息或者过期，说明需要重新受权 session没有任何信息，从头进行OAuth2.0受权
	* session中有openidtime，且没有过期，不做处理
	*/	
	public function wxOauth(){
		$current_url = $this->get_url ();

		if (isset ( $_REQUEST ["code"] )) {
			$code = htmlspecialchars ( $_REQUEST ["code"] );
			$res = $this->wxGetToken();
			if ($res === false || empty ( $res ["access_token"] )) {
				 show_oauth_error('授权失败：获取accesstoken错误');
			}
			$userinfo = $this->wxGetuserinfo( $res ["access_token"], $code);
			if ($userinfo === false ) {
				 show_oauth_error('获取userinfo错误');
			}
			$authinfo = array (
					'access_token' => $res ["access_token"],
					'expires_in' => $res ["expires_in"],
					'tokentime' =>  $res ["tokentime"],
					'UserId' => $userinfo ["UserId"],
			);
				
			$this->session->set_userdata ( $authinfo );
		
		} else {
			if ($this->session->has_userdata ( "UserId" )) {
					// 授权信息未过期，且保存在session中
			} else {
				$this->wechatWebAuth ( $current_url );
			}
		}		
	}
	


	/**
	 * 第一步：企业获取code
	 * @param string $redirct_url 授权后返回url
	 * @param string $scope 应用授权作用域，snsapi_base （不弹出授权页面，直接跳转，只能获取用户openid）
              snsapi_userinfo （弹出授权页面，可通过openid拿到昵称、性别、所在地。并且，即使在未关注的情况下，只要用户授权，也能获取其信息）
	 **/
	private function wechatWebAuth($redirct_url = ""){
		$scope = "snsapi_base"; //应用授权作用域，此时固定为：snsapi_base
		$redirct_url = $redirct_url === ""?$this->config->item("wx_webauth_callback_url"):urlencode($redirct_url);
		$wxurl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->config->item("wx_appID")."&redirect_uri=".$redirct_url."&response_type=code&scope=".$scope."&state=STATE#wechat_redirect";
		header('Location:'.$wxurl);
	}
	
	
	
	/**
	 * 第二步：获取AccessToken
	 * @param null
	 **/
	public function wxGetToken(){
		$url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=".$this->config->item("wx_appID")."&corpsecret=".$this->config->item("wx_appsecret");
		$Token = $this->curlGetInfo($url);
		$data = json_decode($Token, true);
		if (isset($data['errcode'])) {
			show_oauth_error('授权失败：获取token错误');
			return false;
		}
		$data['tokentime'] = time();
		return $data;
	}
	


	/**
	 * 第三步：根据code获取成员信息
	 * @param $access_token,$code
	 */
	function wxGetuserinfo($access_token,$code){
		$url2="https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo?access_token=$access_token&code=$code";
		$content2 = $this->curlGetInfo($url2);
		$data=json_decode($content2,true);  //微信获取用户信息
		if (isset($data['errcode'])) {
				show_oauth_error('获取userinfo错误');
				return false;
		}
		
		if (isset($data['OpenId'])) {
			show_error('必须先关注企业号！');
			return false;
		}

		return $data;
	
	}
	
		
	/*
	 * 抓取网页
	 */
	private function curlGetInfo($url) {
		$ch = curl_init ();
		
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, "GET" );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
		curl_setopt ( $ch, CURLOPT_SSLVERSION, 1 );
		curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)' );
		curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt ( $ch, CURLOPT_AUTOREFERER, 1 );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
		
		$info = curl_exec ( $ch );
		
		if (curl_errno ( $ch )) {
			log_message('error', 'curl Errno' . curl_error ( $ch ));
			return false;
		}
		
		return $info;
	}
	
	
	/**
	 * 获取当前页面完整URL地址
	 */
	private function get_url() {
		$sys_protocal = isset ( $_SERVER ['SERVER_PORT'] ) && $_SERVER ['SERVER_PORT'] == '443' ? 'https://' : 'http://';
		$php_self = $_SERVER ['PHP_SELF'] ? $_SERVER ['PHP_SELF'] : $_SERVER ['SCRIPT_NAME'];
		$path_info = isset ( $_SERVER ['PATH_INFO'] ) ? $_SERVER ['PATH_INFO'] : '';
		$relate_url = isset ( $_SERVER ['REQUEST_URI'] ) ? $_SERVER ['REQUEST_URI'] : $php_self . (isset ( $_SERVER ['QUERY_STRING'] ) ? '?' . $_SERVER ['QUERY_STRING'] : $path_info);
		return $sys_protocal . (isset ( $_SERVER ['HTTP_HOST'] ) ? $_SERVER ['HTTP_HOST'] : '') . $relate_url;
	}

}
