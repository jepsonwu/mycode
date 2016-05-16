<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Administrator
 * Date: 12-11-15
 * Time: 下午1:43
 * To change this template use File | Settings | File Templates.
 */
class Zend_Xml_Ele extends DOMElement
{
	protected $_ele = null;

	public function __construct(DOMElement $ele = null)
	{
		if(null !== $ele)
		{
			$this->_ele = $ele;
		}
		$this->init();
	}

	public function init(){}

	public function r(DOMElement $ele)
	{
		$this->_ele = $ele;
		return $this;
	}

	public function g()
	{
		return $this->_ele;
	}

	/*
	 * 给元素设置属性
	 */
	public function setAttr(array $attrs)
	{
		foreach($attrs as $attr=>$v)
		{
			$this->_ele->setAttribute($attr,$v);
		}
		return $this;
	}

	/*
	 * 添加其他元素进此元素
	 */
	public function append($ele)
	{
		if($ele instanceof Zend_Xml_Ele)
			$this->_ele->appendChild($ele->_ele);
		else
			$this->_ele->appendChild($ele);
		return $this;
	}

}
