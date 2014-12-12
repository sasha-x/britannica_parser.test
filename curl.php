<?php
/* Base curl class, create curl handler (ch) and provide send method with result return */
/* Список полей запроса: */
#Data array format
#
#0:CURLOPT_URL
#1:CURLOPT_POSTFIELDS
#2:CURLOPT_COOKIE
#3:CURLOPT_HEADER
#4:CURLOPT_NOBODY
#5:CURLOPT_REFERER
#6:CURLOPT_COOKIEFILE
#7:CURLOPT_USERAGENT
#8:CURLOPT_BINARYTRANSFER
#9:CURLOPT_HTTPHEADER

//include_once("host.php");
define('COOKIEDIR', './tmp');

class CURL
{
#CURL handler
var $ch;
#Error
var $error;
#Responce Header
var $header;
#Body of responce
var $body;

var $cookie_name = "common";		//TODO
var $debug = 0;
var $timeout = 60;					//таймаут операции
var $sleep = 0;						//таймаут между операциями

#Constructor initialise curl handler
function __construct ($debug = 0)	
{
	$this->debug = $debug;

	$this->ch = curl_init();
	curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
	//Debug
	curl_setopt($this->ch, CURLOPT_VERBOSE , $this->debug);
	curl_setopt($this->ch, CURLINFO_HEADER_OUT, $this->debug);
	//Turn off SSL verification
	curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
	//set timeout
	curl_setopt( $this->ch, CURLOPT_CONNECTTIMEOUT, 60 );
    curl_setopt( $this->ch, CURLOPT_TIMEOUT, $this->timeout );
	
	curl_setopt( $this->ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt( $this->ch, CURLOPT_MAXREDIRS, 20 );
	curl_setopt($this->ch, CURLOPT_USERAGENT, "Opera/9.80 (Windows NT 6.1) Presto/2.12.388 Version/12.17");
	curl_setopt($this->ch, CURLOPT_ENCODING, '');
	curl_setopt($this->ch, CURLOPT_AUTOREFERER, 1);
	
	return true;
}

function timeout($timeout = 60)
{
    curl_setopt( $this->ch, CURLOPT_TIMEOUT, $timeout );
}

function __destruct()
{
	curl_close($this->ch);
}

#Execute configured handler and return result
private function exec ()
{
	$start = microtime(true);
	$result = curl_exec($this->ch);
	$time =  microtime(true) - $start;
	
	if($this->debug){
		txtlog("\t -- exec :: $time:");
		txtlog(print_r(curl_getinfo($this->ch), true));	
		
	}
	if(curl_errno($this->ch) != 0){
		txtlog ('CURL_error: ' . curl_errno($this->ch) . ', ' . curl_error($this->ch));
		//To supress stupid curl error 18
		$this->error = curl_errno($this->ch);
		return false;
	}
	return $result;
}

#Configure handler, send request and return result
function send ($data, $plain = 0)
{
	$this->setopts ($data);
	
	$r = $this->exec();
	
	$header_size = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
	$this->exec_time = curl_getinfo($this->ch, CURLINFO_TOTAL_TIME);
	
	$this->sleep();
	
	if($plain){			//вернуть как есть
		return $r;
	}else{				//разобрать на заголовок и тело
		$this->header = trim(substr($r, 0, $header_size));
		$this->body = trim(substr($r, $header_size));
		
		if($this->debug)
			echo "\n ---------- RESP HEADER ------------- \n $this->header \n ----------------------- \n";
		if($this->debug == 2)
			echo "\n\n\n\n ---------- RESP BODY ------------- \n ".htmlentities($this->body)." \n ----------------------- \n";
		
		return array($this->header, $this->body);
	}
	
}

function sleep()
{
	sleep($this->sleep);
}

/* Ставим параметры запроса
 * Дописана под многократное использование */
function setopts ($data)
{
	if($this->debug)
		txtlog ($data);
	curl_setopt($this->ch, CURLOPT_URL, $data[0]);
	//curl_setopt($this->ch, CURLOPT_INTERFACE, $this->ip);
	if(!empty($data[1])){
		curl_setopt($this->ch, CURLOPT_POST, 1);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query ($data[1]));
	}else{
		curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
	}
	
	if(isset($data[2])){
		curl_setopt($this->ch, CURLOPT_COOKIE, $data[2]);
	}else{		
		if(!empty($data[6]))
			$cookie_name = $data[6];
		else
			$cookie_name = $this->cookie_name;
			
		$fcookie = COOKIEDIR . "/" . $cookie_name . ".cookie";
		
		curl_setopt($this->ch, CURLOPT_COOKIEFILE, $fcookie);
		curl_setopt($this->ch, CURLOPT_COOKIEJAR, $fcookie);
	}
	
	if($data[3] === 0)
		curl_setopt($this->ch, CURLOPT_HEADER, 0);
	else
		curl_setopt($this->ch, CURLOPT_HEADER, 1);
		
	curl_setopt($this->ch, CURLOPT_NOBODY, $data[4]);
	curl_setopt($this->ch, CURLOPT_REFERER, $data[5]);
	
	/*if($data[7])
		curl_setopt($this->ch, CURLOPT_USERAGENT, $data[7]);*/
	
	//curl_setopt($this->ch, CURLOPT_BINARYTRANSFER, intval($data[8]));
	
	if($data[9]){
		$header = $data[9];
	}else{
		$header = array(
				"Accept: text/html, application/xml;q=0.9, application/xhtml+xml, image/png, image/webp, image/jpeg, image/gif, image/x-xbitmap, */*;q=0.1",
				"Accept-Language: ru-RU,ru;q=0.9,en;q=0.8",
				"Accept-Encoding: gzip, deflate",
				"Connection: Keep-Alive",
				"Keep-Alive: 300",
		);
	}
	curl_setopt($this->ch, CURLOPT_HTTPHEADER, $header);

}

}
?>