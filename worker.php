<?php
define('E_ERRORS_REPORTING', E_ERROR | E_PARSE | 	E_COMPILE_ERROR | E_RECOVERABLE_ERROR | E_USER_ERROR | E_CORE_ERROR);	//E_ALL
ini_set('error_reporting',  E_ERRORS_REPORTING);

include_once("helpers.php");
include_once("curl.php");
include_once("parsers.php");

class Topic {

	public $id = 0;
	public $url;
	public $title;
	public $altTitles = [];
	public $textHtml;
	public $textPlain;
	public $writtenBy;
	public $authors = [];
	
	public $medias = [];
	
	public $status;
	public $commentsCounter = 0;
	public $comments = [];
	public $firstUpdate;
	public $lastUpdate;
	public $externalLinks = [];
	
}

class Worker {

	const BASE_URL = "http://www.britannica.com";
	const CAT_URL = "http://www.britannica.com/topic-browse";
	const TOPIC_URL = "http://www.britannica.com/EBchecked/topic/";
	
	protected $curl;
	public $startCategory;
	
	public function __construct($curl){
		$this->curl = $curl;
	}
	
	public function setCategory($startCategory){
		$this->startCategory = $startCategory;
	}
	
	protected function getPage($url){
		flog ("$url\n");
		list(, $body) = $this->curl->send([$url]);
		return $body;
	}
	
	
	
	function start(){
		$startUrl = self::CAT_URL ."/". $this->startCategory;
		
		$topics = [];
		$i = 0;
		do{
			$i++;
			$pageUrl = $startUrl ."/". $i;
			flog ("$i\t");

			$body = $this->getPage($pageUrl);
			$parser = new Britannica_Parser($body);
			$topicUrls = $parser->getTopics();
			foreach($topicUrls as $turl){
				$topicUrl = self::BASE_URL . $turl;
				$body = $this->getPage($topicUrl);
				$tparser = new Britannica_Topic_Parser($body);
				$topic = $tparser->getData();
				
				pr($topic);		
				
				$topics[] = $topic;
			}
		}while($i <= $parser->pageCounter);
		
		//may be write to db here
		
	}
}

$startCategory = "Mathematics";

$debug = 0;
$curl = new CURL($debug);
$curl->sleep = 1;		

$worker = new Worker($curl);
$worker->setCategory($startCategory);

$worker->start();

?>