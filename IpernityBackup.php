<?php

/* PLEASE ADD HERE YOUR API KEY AND SECRET */

$APIKEY='XXX';
$APISECRET='XXX';


/* dev params */	
	if ($APIKEY=='XXX') include_once('./dev/apikey.php'); 
/*  */





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
		$this->username=$apiJson->user->username;

		$this->rep='data/'.self::escape($this->username,true);
		@mkdir( $this->rep );
		@mkdir( $this->rep.'/tmp' );

		file_put_contents($this->rep.'/user.json', $apiJsonText);		

	}



	function getDocumentList() {
		echo 'Get document list'.PHP_EOL;
		
		$page=1; $maxpage=1;
		while ($page<=$maxpage) {
			echo ' get document list - page '.$page;
			if ($maxpage>1) echo ' of '.$maxpage;
			echo PHP_EOL;

			$dest=$this->rep.'/tmp/docList.page'.$page.'.json';

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
			
			file_put_contents($dest, $apiJsonText);

			$maxpage=$apiJson->docs->pages;
			$this->docs =  array_merge( $this->docs, $apiJson->docs->doc);	

			$page++;
		}

		echo ' save full document list'.PHP_EOL;
		file_put_contents($this->rep.'/docList.json', json_encode(array('doc'=>$this->docs)));
	}




	function getAlbumList() {
		echo 'Get album list'.PHP_EOL;
		
		$page=1; $maxpage=1;
		while ($page<=$maxpage) {
			echo ' get album list - page '.$page;
			if ($maxpage>1) echo ' of '.$maxpage;
			echo PHP_EOL;

			$dest=$this->rep.'/tmp/albumList.page'.$page.'.json';

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
			file_put_contents($dest, $apiJsonText);

			$maxpage=$apiJson->albums->pages;
			$this->albums =  array_merge( $this->albums, $apiJson->albums->album);
			$page++;
		}

		echo ' save full album list'.PHP_EOL;
		file_put_contents($this->rep.'/albumList.json', json_encode(array('album'=>$this->albums)));
	}	


	function getAlbumsDocumentList() {
		echo 'Get albums list'.PHP_EOL;
		@mkdir( $this->rep.'/albums/' );

		foreach ($this->albums as $k=>$album) {
			echo ' get album "'.$album->title.'" list'.PHP_EOL;
			$albumPages=array();

			$page=1; $maxpage=1;
			while ($page<=$maxpage) {
				echo '      page '.$page;
				if ($maxpage>1) echo ' of '.$maxpage;
				echo PHP_EOL;

				$dest=$this->rep.'/tmp/albumDocument.ID'.$album->album_id.'.page'.$page.'.json';

				$apiJsonText= $this->ApiService->call(
					'album.docs.getList', 
					array(
						'album_id'=>$album->album_id,
						'extra'=> 'dates,geo,medias,original,link',
						'page'=>$page, 
						'per_page'=>100
						)
					);

				$apiJson=json_decode($apiJsonText);
				ApiService::checkStatus($apiJson);			
				file_put_contents($dest, $apiJsonText);

				$maxpage=$apiJson->album->docs->pages;
				$albumPages =  array_merge( $albumPages, $apiJson->album->docs->doc);
				$page++;
			}

			echo ' save full album "'.$album->title.'" list'.PHP_EOL;
			file_put_contents($this->rep.'/albums/albumDocument.ID'.$album->album_id.'.'.self::escape($album->title,true).'.json', json_encode(array('album'=>$albumPages)));

		}
		
		
	}	




	function getTags() {
		echo 'Get user tags'.PHP_EOL;

			$dest=$this->rep.'/tags.json';
		
			$apiJsonText= $this->ApiService->call(
				'tags.user.getList',
				array(
					'user_id'=>$this->user_id,
					'type'=>'keyword',
					'count'=>1000
					)
			);

			$apiJson=json_decode($apiJsonText);
			ApiService::checkStatus($apiJson);
			file_put_contents($dest, $apiJsonText);	

	}





	function getDocumentsData() {
		echo 'Get documents data'.PHP_EOL;

		@mkdir( $this->rep.'/doc/' );

		foreach ($this->docs as $k=>$doc) {
			$dest=$this->rep.'/doc/doc'.self::escape($doc->doc_id).'.'.self::escape($doc->title,true).'.json';

			echo ' get doc data '.($k+1).' of '.count($this->docs).' - ID.'.$doc->doc_id.' '.$doc->title.PHP_EOL;

			if (!file_exists($dest)) {			

			$apiJsonText= $this->ApiService->call(
				'doc.get',
				array(
					'doc_id'=>$doc->doc_id, 
					'extra'=>'tags,notes,geo,md5'
					)
			);

			$apiJson=json_decode($apiJsonText);
			ApiService::checkStatus($apiJson);
			file_put_contents($dest, $apiJsonText);	
		}
			else $apiJson=json_decode(file_get_contents($dest));
		
		$this->docs[$k] = (object) array_merge((array) $this->docs[$k], (array) $apiJson->doc);	

		}

	}





	function getDocumentsMedia() {
		echo 'Get documents media'.PHP_EOL;

		@mkdir( $this->rep.'/doc/' );

		foreach ($this->docs as $k=>$doc) {
			@mkdir( $this->rep.'/doc/'.self::escape($doc->media) );

			echo ' get doc media '.($k+1).' of '.count($this->docs).' - '.strtoupper($doc->media).' ID.'.$doc->doc_id.' '.$doc->title;

			if (isset($doc->medias)) {
				foreach ($doc->medias->media as $media)
					self::download($media->url, $this->rep.'/doc/'.self::escape($doc->media).'/'.$doc->doc_id.'.'.self::escape($doc->title,true).'.'.$media->label.'.'.$media->format);
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

				self::download($select_thumb->url, $this->rep.'/doc/'.self::escape($doc->media).'/'.$doc->doc_id.'.'.self::escape($doc->title,true).'.'.$select_thumb->label.'.'.$select_thumb->ext);
			}

		}

	}




	static function escape($text, $keepspace=false) {
		$text = iconv('UTF-8','ASCII//TRANSLIT',$text); // remove accents
		if ($keepspace) return preg_replace("/[^\w]+/", "_", $text);
			else  return preg_replace("/[^ \w]+/", "_", $text);
	}

	static function download($ori,$dest) {
		if (!file_exists($dest)) {
			echo '         ----> downloading: '.$ori;
			copy($ori, $dest);
		}
		echo PHP_EOL;
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
	$IB->getAlbumList();
	$IB->getAlbumsDocumentList(); 
	$IB->getTags();
	$IB->getDocumentsData();
	$IB->getDocumentsMedia();

?>