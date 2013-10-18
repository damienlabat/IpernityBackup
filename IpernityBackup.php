<?php


$APIKEY='XXX';


/**/	if ($APIKEY=='XXX') include_once('./dev/apikey.php'); /**/





/**
* 
*/
class ApiService
{
	
	function __construct($url='',$apikey='')
	{
		$this->setUrl($url);
		$this->setApiKey($apikey);
	}

	public function setUrl($url) {
		$this->url=$url;
	}

	public function setApiKey($apikey) {
		$this->apikey=$apikey;
	}

	public function call(String $method, Array $data, $format='json') {

	}

	public function dump() {var_dump($this);}
}






/**
* 
*/
class IpernityBackup
{
	
	function __construct($username=null)
	{
		global $APIKEY;
		$this->username= $username;
		$this->apikey= $APIKEY;
	}


	public function whoami() {
		echo $this->username;
		var_dump($this);
	}
}










/* LETS GO */

//init ApiService
	$ApiService=new ApiService('http://api.ipernity.com/api/',$APIKEY);
	$ApiService->dump();


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
	$IB= new IpernityBackup($username);

//test
$IB->whoami();

?>