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
	 * OAuth2.0受权
	* http://mp.weixin.qq.com/wiki/17/c0f37d5704f0b64713d5d2c37b468d75.html
	* 共有2个步骤：用户同意授权，获取code-->第二步：通过code换取网页授权access_token
	* 2个可选步骤：第三步：刷新access_token；第四步：拉取用户信息(需scope为 snsapi_userinfo) 算法步骤：
	* 如果连接参数中带有code参数,说明是由第一步成功返回，通过code换取网页授权access_token和用户信息，将用户信息存入session，以免对同一用户反复要求受权
	* 如果连接参数中不带有code参数，说明不是来自微信OAuth2.0受权返回的页面
	* 判断session的信息是否过期，如果没有信息或者过期，说明需要重新受权 session没有任何信息，从头进行OAuth2.0受权
	* session中有openidtime，且没有过期，不做处理
	* session中有openidtime，且过期，用refreshtoken重新获取信息
	*/	
	public function wxOauth(){
		$current_url = $this->get_url ();

		if (isset ( $_REQUEST ["code"] )) {
			$code = htmlspecialchars ( $_REQUEST ["code"] );
			$res = $this->wxGetTokenWithCode ( $code );
			if ($res === false || empty ( $res ["access_token"] )) {
				 show_oauth_error('授权失败：获取token错误');
			}
			$userinfo = $this->wxGetuserinfo( $res ["access_token"], $res ["openid"]);
			if ($userinfo === false ) {
				 show_oauth_error('获取userinfo错误');
			}
			$authinfo = array (
					'access_token' => $res ["access_token"],
					'refresh_token' => $res ["refresh_token"],
					'openid' => $res ["openid"],
					'scope' => $res ["scope"],
					'expires_in' => $res ["expires_in"],
					'openidtime' => time (),
					'nickname' => $userinfo['nickname'],
			);
				
			$this->session->set_userdata ( $authinfo );
		
		} else {
			if ($this->session->has_userdata ( "openidtime" )) {
		
				$dtime = time () - $this->session->userdata ( "openidtime" );
		
				if ($dtime > $this->config->item ( "wx_webauth_expire" )) {
					$refresh_token = $this->session->userdata ( 'refresh_token' );
					$this->session->unset_userdata ( 'access_token' );
					$this->session->unset_userdata ( 'refresh_token' );
					$this->session->unset_userdata ( 'openid' );
					$this->session->unset_userdata ( 'scope' );
					$this->session->unset_userdata ( 'openidtime' );
					$res = $this->refreshToken ( $refresh_token );
					if ($res === false) {
						 show_oauth_error('授权失败：刷新token错误');
					}
					$userinfo = $this->wxGetuserinfo ( $res ["access_token"], $res ["openid"] );
					if ($userinfo === false) {
						show_oauth_error('获取userinfo错误');
					}
					$authinfo = array (
							'access_token' => $res ["access_token"],
							'refresh_token' => $res ["refresh_token"],
							'openid' => $res ["openid"],
							'scope' => $res ["scope"],
							'expires_in' => $res ["expires_in"],
							'openidtime' => time (),
							'nickname' => $userinfo['nickname'],
					);
					$this->session->set_userdata ( $authinfo );
				} else {
					// 授权信息未过期，且保存在session中
				}
		
			} else {
				$this->wechatWebAuth ( $current_url, 'snsapi_userinfo' );
			}
		}		
	}
	


	/**
	 * 第一步：用户同意授权，获取code
	 * @param string $redirct_url 授权后返回url
	 * @param string $scope 应用授权作用域，snsapi_base （不弹出授权页面，直接跳转，只能获取用户openid）
              snsapi_userinfo （弹出授权页面，可通过openid拿到昵称、性别、所在地。并且，即使在未关注的情况下，只要用户授权，也能获取其信息）
	 **/
	private function wechatWebAuth($redirct_url = "",$scope = "snsapi_base"){
		$redirct_url = $redirct_url === ""?$this->config->item("wx_webauth_callback_url"):urlencode($redirct_url);
		$wxurl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->config->item("wx_appID")."&redirect_uri=".$redirct_url."&response_type=code&scope=".$scope."&state=STATE#wechat_redirect";
		header('Location:'.$wxurl);
	}
	
	
	/**
	 * 第二步：通过code换取网页授权access_token
	 * @param string $code wechatWebAuth 返回的code
	 **/
	private function wxGetTokenWithCode($code){
		if(!isset($code)){
			return false;
		}
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->config->item("wx_appID")."&secret=".$this->config->item("wx_appsecret")."&code=".$code."&grant_type=authorization_code";
		$Token = $this->curlGetInfo($url);
		$data = json_decode($Token, true);
		if (isset($data['errcode'])) {
				show_oauth_error('授权失败：获取token错误');
				return false;
		}
		return $data;
	}
	
	
	
	/**
	 * 第三步：刷新access_token（如果需要）
	 * @param string $appid refresh_token 
	 **/
	private function refreshToken($refresh_token) {
		if(empty($refresh_token)){
			return false;
		}
		$url = 'https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=' .$this->config->item("wx_appID"). '&grant_type=refresh_token&refresh_token=' . $refresh_token;
		$Token = $this->curlGetInfo($url);
		$data = json_decode($Token, true);
		if (isset($data['errcode'])) {
				show_oauth_error('授权失败：刷新token错误');
				return false;
		}
		return $data;
	}
	
	

	/**
	 * 第四步：拉取用户信息(需scope为 snsapi_userinfo)
	 * @param $access_token,$openid
	 */
	function wxGetuserinfo($access_token,$openid){

		$url2="https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid&lang=zh_CN";
		$content2 = $this->curlGetInfo($url2);
		$o2=json_decode($content2,true);  //微信获取用户信息

		if (isset($data['errcode'])) {
				show_oauth_error('获取userinfo错误');
				return false;
		}
		
		//处理昵称里的特殊字符
		/*
		 $str_nickname=substr($content2,strpos($content2,",")+1);
		$str_nickname=substr($str_nickname,12,strpos($str_nickname,",")-13);
	
		$data=array('nickname'=>'','heading'=>'');
		$data['nickname']=base64_encode($str_nickname);
		$data['headimgurl']=$o2['headimgurl'];
		*/
		 
		//var_dump($o2);
		 
		return $o2;
	
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
