<?php
// pour la liste des api voir "aide" dans la console freebox ou 
//   http://dev.freebox.fr/sdk/
class FreeboxApp {
	private $url;
	private $box;
	private $id='fr.freebox.unknown';
	private $token=null;
	private $sessionToken=null;
	public $debug=false;
	function __construct($appId, $token, $url="http://mafreebox.freebox.fr") {
		$this->url=$url;
		$this->id=$appId;
		$this->token=$token;
		$this->sessionToken=null;
		$this->version();
	}	
	function version() {
		$path="api_version";
		$content=file_get_contents("$this->url/$path");
		return $this->box=json_decode($content);
	}
	function call($api_url,$params=array(), $method=null) {
		if (!$method) 
			$method=(!$params)?'GET':'POST';
		$rurl=$this->url.$this->box->api_base_url.'v'.intval($this->box->api_version).'/'.$api_url;
		if ($this->debug)
			echo "\n<hr/><b>$method to $rurl (".print_r($params,true).")]</b><br>\n";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $rurl);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		if ($method=="POST") {
			curl_setopt($ch, CURLOPT_POST, true);
		} elseif ($method=="DELETE") {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		} elseif ($method=="PUT") {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		}
		if ($params)
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
		if ($this->sessionToken)
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Fbx-App-Auth: $this->sessionToken"));
		$content = curl_exec($ch);
		curl_close($ch);
		$r=json_decode($content);
		if ($this->debug)
			echo "Result:<br/><pre>".print_r($r,true)."</pre><hr/>";
		return $r;
	}
	function authorize($name, $version, $device) {
		$r=$this->call("login/authorize", array(
				'app_id'=>$this->id,
				'app_name'=>$name,
				'app_version'	=> $version,
				'device_name'	=> $device
			));
		if ($r->success)
			$this->token=$r->result->app_token;
		return $r;
	}
	function login() {
		$rc=$this->call("login");
		$c=$rc->result->challenge;

		$password = hash_hmac('sha1', $c, $this->token);

		$r=$this->call("login/session", 
			array('app_id' => $this->id,
			    'password' => $password));
		if ($r->success)
			$this->sessionToken=$r->result->session_token;
		return $r;
	}
}
