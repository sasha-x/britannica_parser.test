<?php

class Parser {
	
	protected $xpath;
	protected $nodes;
	
	function __construct($html){
		$doc = new DOMDocument();
		$doc->loadHTML($html);
		$this->xpath = new DOMXpath($doc);
	}
	
	public function query($query){
		$this->nodes = $this->xpath->query($query);
		return $this->nodes;
	}
	
	public function sizeof(){
		return $this->nodes->length;
	}
	
	public function values(){
		foreach ($this->nodes as $node) {
			$vals[] = $node->nodeValue;
		}
		return $vals;
	}
	
	public function value(){
		return $this->values()[0];
	}
}

class Britannica_Parser extends Parser {
	
	const PAGINATION_XPATH = "//div[@class='pagination pagination-centered']/ul/li/a/@href";
	const TOPICS_XPATH = "//ul[@class='tb-items']/li/h4/a/@href";
	
	public $pages;
	public $pageCounter;
	
	public function __construct($html){
		parent::__construct($html);
		
		$this->pages = $this->getPagination();		//TODO: get it once by category
		$this->pageCounter = sizeof($this->pages);
	}
	
	public function getPagination(){
		$this->query(self::PAGINATION_XPATH);
		$urls = $this->values();
		
		return $urls;
	}
	
	public function getTopics(){
		$this->query(self::TOPICS_XPATH);
		$urls = $this->values();
		return $urls;
	}
}

class Britannica_Topic_Parser extends Parser {
	const TITLE_XPATH = "//h1/span/text()";
	const TEXT_XPATH = "//section[@class='eb-topic-section']";
	const WRITTENBY_XPATH = "//div[@class='subheading hide-on-edit']/span[1]/a[1]/text()";
	//...
	
	protected $topicData;
	
	public function __construct($html){
		parent::__construct($html);
		
		$this->topicData = new Topic;
	}
	
	public function getData(){
		$topic = $this->topicData;
		$topic->title = $this->value($this->query(self::TITLE_XPATH));
		$topic->textPlain = $this->value($this->query(self::TEXT_XPATH));
		$topic->writtenBy = $this->value($this->query(self::WRITTENBY_XPATH));
		//...
		
		return $topic;
	}
}

?>