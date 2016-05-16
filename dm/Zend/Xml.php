<?php

require_once 'Zend/Xml/Ele.php';

class Zend_Xml
{

	protected $_xml = null;

	public function __construct()
	{
		$this->init();
	}

	//initalize function
	public function init(){}

	public function g()
	{
		return $this->_xml;
	}


	/*
	 * 创建一个新的空白xml文档
	 */
	public function create()
	{
		$this->_xml = new DOMDocument('1.0','UTF-8');
		return $this->_xml;
	}

	/*
	 * 加载xml
	 * $type false为字符加载，true为文件加载
	 */
	public function load($param,$type = false)
	{
		$this->_xml = new DOMDocument();
		if($type){
			$this->_xml->load($param);
		}else{
			$param = preg_replace("/\n\r/","",$param);
			$param = preg_replace("/\n/","",$param);
			$param = preg_replace("/\r/","",$param);
			$param = preg_replace("/>(\s*?)</","><",$param);
			$this->_xml->loadXML($param);
		}
		return $this->_xml;
	}

	/*
	 * 创建一个普通元素
	 */
	public function createEle($tagName,$tagValue = null)
	{
		$ele = $this->_xml->createElement($tagName,$tagValue);
		return new Zend_Xml_Ele($ele);
	}

	/*
	 * 创建一个带有命名空间的元素
	 * DOMElement DOMDocument::createElementNS ( string $namespaceURI , string $qualifiedName [, string $value ] )
	 */
	public function  createEleNS($namespaceURI,$qualifiedName,$value = null)
	{
		$ele = $this->_xml->createElementNS($namespaceURI,$qualifiedName,$value);
		return new Zend_Xml_Ele($ele);
	}

	/*
	 * 添加元素进此文档
	 */
	public function append($ele)
	{
		if($ele instanceof Zend_Xml_Ele)
			$this->_xml->appendChild($ele->g());
		else
			$this->_xml->appendChild($ele);
	}

	/*
	 * 返回xml内容
	 */
	public function save()
	{
		$this->_xml = $this->_xml->saveXML();
		return $this->_xml;
	}

}