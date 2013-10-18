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

		if ($json->api->status!="ok")
			die ($json->api->status.PHP_EOL.'  '.$json->api->message.PHP_EOL);
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
		$this->docs=array();
		$this->albums=array();
		@mkdir('data');

		$this->getUserData();

		/*
		$this->getDocumentList();
		$this->getAlbumList();

		$this->getDocumentsData();
		$this->getDocumentsMedia();
		*/	
		



		//$this->dump();
	}

	

	function getUserData() {
		echo 'Get user data'.PHP_EOL;

		$apiJsonText= $this->ApiService->call(
			'user.get', 
			array(
				'user_id'=>$this->username
				)
			);

		$apiJson=json_decode($apiJsonText);
		ApiService::checkStatus($apiJson);
		$this->user_id=$apiJson->user->user_id;

		$this->rep='data/'.self::escape($this->username);
		@mkdir( $this->rep );

		file_put_contents($this->rep.'/user.json', $apiJsonText);		

	}



	function getDocumentList() {
		echo 'Get document list'.PHP_EOL;
		
		$page=1; $maxpage=1;
		while ($page<=$maxpage) {
			echo ' get document list - page '.$page;
			if ($maxpage>1) echo ' of '.$maxpage;
			echo PHP_EOL;

			$apiJsonText= $this->ApiService->call(
				'doc.getList',
				array(
					'user_id'=>$this->user_id, 
					'page'=>$page, 
					'per_page'=>100
					)
			);

			$apiJson=json_decode($apiJsonText);
			ApiService::checkStatus($apiJson);
			$maxpage=$apiJson->docs->pages;
			file_put_contents($this->rep.'/docList'.$page.'.json', $apiJsonText);
			//$this->docs[]=$apiJson->docs->doc;
			$this->docs =  array_merge( $this->docs, $apiJson->docs->doc);	

			$page++;
		}

		echo ' save full document list'.PHP_EOL;
		file_put_contents($this->rep.'/docList_full.json', json_encode(array('doc'=>$this->docs)));
	}




	function getAlbumList() {
		echo 'Get album list'.PHP_EOL;
		
		$page=1; $maxpage=1;
		while ($page<=$maxpage) {
			echo ' get album list - page '.$page;
			if ($maxpage>1) echo ' of '.$maxpage;
			echo PHP_EOL;

			$apiJsonText= $this->ApiService->call(
				'album.getList', 
				array(
					'user_id'=>$this->user_id,
					'empty'=>1,
					'page'=>$page, 
					'per_page'=>100
					)
				);

			$apiJson=json_decode($apiJsonText);
			ApiService::checkStatus($apiJson);
			$maxpage=$apiJson->albums->pages;
			file_put_contents($this->rep.'/albumList'.$page.'.json', $apiJsonText);
			$this->albums[]=$apiJson->albums->album;
			$page++;
		}

		echo ' save full album list'.PHP_EOL;
		file_put_contents($this->rep.'/albumList_full.json', json_encode(array('album'=>$this->albums)));
	}	






	function getDocumentsData() {
		echo 'Get documents data'.PHP_EOL;

		@mkdir( $this->rep.'/doc/' );

		array_splice($this->docs, 5); // TEST

		foreach ($this->docs as $k=>$doc) {

			echo ' get doc data '.($k+1).' of '.count($this->docs).' - ID.'.$doc->doc_id.' '.$doc->title.PHP_EOL;

			$apiJsonText= $this->ApiService->call(
				'doc.get',
				array(
					'doc_id'=>$doc->doc_id, 
					'extra'=>'tags,notes,geo,md5'
					)
			);

			$apiJson=json_decode($apiJsonText);
			ApiService::checkStatus($apiJson);
			file_put_contents($this->rep.'/doc/doc'.self::escape($doc->doc_id).'.json', $apiJsonText);	
			$this->docs[$k] = (object) array_merge((array) $this->docs[$k], (array) $apiJson->doc);	


		}	

	}



	function getDocumentsMedia() {
		echo 'Get documents media'.PHP_EOL;

		@mkdir( $this->rep.'/doc/' );

		foreach ($this->docs as $k=>$doc) {
			@mkdir( $this->rep.'/doc/'.self::escape($doc->media) );

			echo ' get doc media '.($k+1).' of '.count($this->docs).' - '.strtoupper($doc->media).' ID.'.$doc->doc_id.' '.$doc->title.PHP_EOL;

			if (isset($doc->medias)) {
				foreach ($doc->medias->media as $media)
					self::download($media->url, $this->rep.'/doc/'.self::escape($doc->media).'/'.$doc->doc_id.'.'.$media->label.'.'.$media->format);
			}
			else { // pas de media on va se contenter du thumb
				$maxsize=0;
				foreach ($doc->thumbs->thumb as $thumb) {
					$thumb->size=  (integer) $thumb->label;
					if ( $thumb->size > $maxsize ) {
						$select_thumb=$thumb;
						$maxsize=$thumb->size;
						}
				}

				self::download($select_thumb->url, $this->rep.'/doc/'.self::escape($doc->media).'/'.$doc->doc_id.'.'.$select_thumb->label.'.'.$select_thumb->ext);
			}

		}

	}


	static function escape($text) {
		return preg_replace("/[^\w]+/", "", $text);
	}

	static function download($ori,$dest) {
		if (!file_exists($dest)) {
			echo '     ----> downloading: '.$ori.PHP_EOL;
			copy($ori, $dest);
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



	// lets go
	$IB= new IpernityBackup($username,$APIKEY,$APISECRET);
	$IB->getDocumentList();
	//$IB->getAlbumList();
	//$IB->getAlbumDocumentList();  //TODO

	$IB->getDocumentsData();
	$IB->getDocumentsMedia();

?>