<?php

/**
 * {0}
 *  
 * @author Administrator
 * @version 
 */
	

class User extends CI_Model 
{
	public function __construct(){
		parent::__construct();
		$this->load->database();
	}
    
	public function adduser($wxuser = array()){
		if(!isset($wxuser['UserId']) || empty($wxuser['UserId']))
			return false;
		
		$query = $this->db->select('id,login_count')->from('user')->where('UserId',$wxuser['UserId'])->limit(1)->get();
		
		$res = $query->result();
				
		if(empty($res)) {   //如果用户第一次登录，将存入数据库
			$newuser = array(
					'UserId'=>$wxuser['UserId'],
					'create_time' => time(),					
					);
			$this->db->insert('user', $newuser);
		}else{  //记录登录事件
		  $olduser = array(
		  		'last_login_time'=>time(),
		  		'login_count' => $res[0]->login_count+1,	  
		  		);
		  $this->db->update('user',$olduser,array('id'=>$res[0]->id));
		}
	}
}
