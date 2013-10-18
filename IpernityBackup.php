<?php


$APIKEY='XXX';
$APISECRET='XXX';


/**/	if ($APIKEY=='XXX') include_once('./dev/apikey.php'); /**/





/**
* 
*/
class ApiService
{
	
	function __construct($url='',$apikey='',$apisecret='')
	{
		$this->setUrl($url);
		$this->setApiKey($apikey,$apisecret);		
	}

	public function setUrl($url) {
		$this->url=$url;
	}

	public function setApiKey($apikey,$apisecret) {
		$this->apikey=$apikey;
		$this->apisecret=$apisecret;
	}

	public function getUrl($method, $param, $format='json') {

		$param['api_key']= $this->apikey;
		ksort($param);

		$sig='';
		while (list($key, $val) = each($param)) 
			$sig.= $key.$val;
		$sig.=$method.$this->apisecret;

		$param['api_sig']=md5($sig);

		$url=$this->url.$method.'/'.$format.'?'. http_build_query($param);
		
		return $url;

	}

	public function call($method, $param, $format='json') {
		return file_get_contents( $this->getUrl($method, $param, $format='json') );
	}


	static function checkStatus($json) {
		if (is_string($json)) 
			$json=json_decode($json);

		if ($json->api->status=="error")
			die ('  error !'.PHP_EOL.'  '.$json->api->message.PHP_EOL);
	}
}






/**
* 
*/
class IpernityBackup
{	
		
	function __construct($username,$APIKEY,$APISECRET)
	{	
		global 	$APIKEY,$APISECRET;

		$this->ApiService=new ApiService('http://api.ipernity.com/api/',$APIKEY,$APISECRET);
		$this->username= $username;		
		@mkdir('data');

		$this->getUserData();
		$this->getDocumentList();


		
		



		//$this->dump();
	}

	

	function getUserData() {
		echo 'Get user data'.PHP_EOL;
		$apiJsonText= $this->ApiService->call('user.get', array('user_id'=>$this->username));
		$apiJson=json_decode($apiJsonText);
		ApiService::checkStatus($apiJson);
		$this->user_id=$apiJson->user->user_id;

		$this->rep='data/'.$this->username;
		@mkdir( $this->rep );

		file_put_contents($this->rep.'/user.json', $apiJsonText);		

	}



	function getDocumentList() {
		echo 'Get document list'.PHP_EOL;
		$this->docs=array();
		$page=1; $maxpage=1;
		while ($page<=$maxpage) {
			echo ' get document list - page '.$page;
			if ($maxpage>1) echo ' of '.$maxpage;
			echo PHP_EOL;

			$apiJsonText= $this->ApiService->call('doc.getList', array('user_id'=>$this->user_id, 'page'=>$page, 'per_page'=>100));
			$apiJson=json_decode($apiJsonText);
			ApiService::checkStatus($apiJson);
			$maxpage=$apiJson->docs->pages;
			file_put_contents($this->rep.'/docList'.$page.'.json', $apiJsonText);
			$this->docs[]=$apiJson->docs->doc;
			$page++;
		}

	}






	function dump() { var_dump($this); }	
}










/* LETS GO */

	

//get username
	if (isset($argv[1])) {
	   $username = $argv[1];
	}
	else 
	{
		echo PHP_EOL.'username: ';
		$username = stream_get_line(STDIN, 1024, PHP_EOL);
		echo PHP_EOL;
	}
	$IB= new IpernityBackup($username,$APIKEY,$APISECRET);

//echo $ApiService->call('doc.getList', array('user_id'=>'115157', 'per_page'=>100));	
//echo $ApiService->call('user.get', array('user_id'=>$username));

?>