<?php
/**
 * 专门处理手机应用版本信息
 * 
 * 手机版本号信息
 * 例如：2.1.0 主版本号、次版本号、更新号
 * 
 * 主版本号变动：系统大升级，很有可能前后不兼容，一般强制升级
 * 此版本号变动：同个主版本内的更新应该是要向下兼容的
 * 更新号：一般是修改bug
 * 
 * @author Bruce
 * @since 2014/12/31
 */
class DM_Module_MobileVerson {
	const SEPARATOR='.';
	
	private $version=NULL;
	//主要位
	private $major=NULL;
	//次要位
	private $minor=NULL;
	//最小位
	private $minimal=NULL;
	
	public function __construct($version)
	{
		$this->version=trim($version);
		$this->parse();
	}
	
	private function parse()
	{
		$tmp=explode(self::SEPARATOR, $this->version);
		if (count($tmp)!=3){
			throw new Exception('版本号'.$this->version.'不符合规范，请确认');
		}
		$this->major=abs(intval($tmp[0]));
		$this->minor=abs(intval($tmp[1]));
		$this->minimal=abs(intval($tmp[2]));
	}
	
	public function getMajor()
	{
		return $this->major;
	}
	
	public function getMinor()
	{
		return $this->minor;
	}
	
	public function getMinimal()
	{
		return $this->minimal;
	}
	
	public function compare(DM_Module_MobileVerson $rt)
	{
		if ($this->major==$rt->getMajor() && $this->minor==$rt->getMinor() && $this->minimal==$rt->getMinimal()){
			return true;
		}else{
			return false;
		}
	}
	
	public function compareMajor(DM_Module_MobileVerson $rt)
	{
		if ($this->major==$rt->getMajor()){
			return true;
		}else{
			return false;
		}
	}
	
	public function compareMinor(DM_Module_MobileVerson $rt)
	{
		if ($this->major==$rt->getMajor() && $this->minor==$rt->getMinor()){
			return true;
		}else{
			return false;
		}
	}
	
	public function lessThan(DM_Module_MobileVerson $rt)
	{
		if ($this->major<$rt->getMajor()
				|| $this->major==$rt->getMajor() && $this->minor<$rt->getMinor()
				|| $this->major==$rt->getMajor() && $this->minor==$rt->getMinor() && $this->minimal<$rt->getMinimal()){
			return true;
		}else{
			return false;
		}
	}
	
	public function greateThan(DM_Module_MobileVerson $rt)
	{
		return !$this->compare($rt) && !$this->lessThan($rt);
	}
}