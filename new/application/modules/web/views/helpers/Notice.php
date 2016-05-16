<?php

/* $Id$ */

/* Zend_View_Helper_Placeholder_Container_Standalone */
require_once 'Zend/View/Helper/Placeholder/Container/Standalone.php';

/**
 * View helper for relaying notice messages to user through view.
 *
 * @category   Zend
 * @package    Zend_View_Helper
 * @copyright  Copyright (c) Zhongjie.com All Right Reserved.
 * @author     Justin Wu <ezdevelop@gmail.com>
 */
class Zend_View_Helper_Notice extends Zend_View_Helper_Placeholder_Container_Standalone
{
	/**
	 * @var string  message key for automatic translation of title
	 */
	const TRANSLATE_KEY_TITLE = 'Zend_View_Helper_Notice.title';
	
	/**
	 * @var string  title to display before message list
	 */
	protected $_messageTitle = 'Notices';
	
	/**
	 * @var string  registry key for placeholder
	 */
	protected $_regKey = 'Zend_View_Helper_Notice';
	
	/**
	 * Retrieve placeholder for notice messages and optionally set state
	 *
	 * @param  string $message  [optional]
	 * @param  string $setType  [optional]
	 * @return Sofee_View_Helper_Notice
	 */
	public function notice($message = null, $setType = Zend_View_Helper_Placeholder_Container_Abstract::APPEND)
	{
		if ($message) {
			if ($setType == Zend_View_Helper_Placeholder_Container_Abstract::SET) {
				$this->set($message);
			} elseif ($setType == Zend_View_Helper_Placeholder_Container_Abstract::PREPEND) {
				$this->prepend($message);
			} else {
				$this->append($message);
			}
		}
		return $this;
	}
	
	public function toString($indent = null)
	{
		if ($this->count() < 1) {
			return '';
		}
		
		$indent = (null !== $indent) ? $this->getWhitespace($indent) : $this->getIndent();
		
		$indent2 = $indent . $this->getWhitespace(4);
		$indent3 = $indent . $this->getWhitespace(8);
		
		// automatic translation of title
		$title  = $this->view->translate(self::TRANSLATE_KEY_TITLE);
		if ($title != self::TRANSLATE_KEY_TITLE) {
			$this->_messageTitle = $title;
		}
		
		$js = "try { $(this.parentNode).slideUp(500); } catch(e) { this.parentNode.style.display = 'none'; };";
		
		$html = $indent . '<div class="sf-alert">'. PHP_EOL;
		$html .= $indent2 . '<div class="sf-alert-remove" onclick="' . $js . '"></div>'. PHP_EOL;
		$html .= $indent2 . '<h4>' . $this->_messageTitle . '</h4>'. PHP_EOL;
		$html .= $indent2 . '<ul class="sf-alert">'. PHP_EOL;
		
		foreach ($this as $item) {
			//$html .= $indent3 . '<li>' . $this->_escape($item) . '</li>' . $sep;
			$html .= $indent3 . '<li>' . $item . '</li>'. PHP_EOL;
		}
		$html .= $indent2 . '</ul>'. PHP_EOL;
		$html .= $indent . '</div>'. PHP_EOL;
		
		return $html;
	}
}
