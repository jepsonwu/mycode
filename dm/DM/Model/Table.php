<?php
/**
 * 数据库表对象基类
 * 
 * @author Bruce
 * @since 2014/05/22
 */
class DM_Model_Table extends Zend_Db_Table_Abstract
{
    // 操作成功
    const STATUS_OK = 1;
    // 操作失败
    const STATUS_FAILURE = -1;
    // 设置从库后的备份适配器
    private $_adapterBackup=NULL;
    
    protected static function resultArray($flag = true,$msg='',$data = NULL, $extra = NULL) {
        $result = array('flag'=>$flag,'msg'=>$msg);
        if($flag < 0){
            $result['param'] = DM_Controller_Front::getInstance()->getHttpRequest()->getParams();
        }
        if($data!==NULL){
            $result['data'] = $data;
        }
        if($extra!==NULL){
            $result['extra'] = $extra;
        }
    
        return $result;
    }
    
    /**
     * 设置从从数据库获取
     * 
     * 设置后select调用也同样生效，默认是master数据库
     * 
     * @return DM_Model_Table 
     */
    public function fromSalveDB()
    {
        if ($this->_db instanceof Zend_Db_Adapter_Abstract) {
            $this->_adapterBackup=$this->_db;
        }
        $this->_setAdapter(DM_Controller_Front::getInstance()->getHashSlaveDB());
        
        return $this;
    }
    
    /**
     * 调用从库后可以调用restoreOriginalAdapter恢复原来的适配器
     * 
     * @return DM_Model_Table
     */
    public function restoreOriginalAdapter()
    {
        if ($this->_adapterBackup instanceof Zend_Db_Adapter_Abstract) {
            $this->_setAdapter($this->_adapterBackup);
        }
        
        return $this;
    }
    
    /**
     * 通过主键获取
     * 
     * @return Zend_Db_Table_Row_Abstract
     */
    public function getByPrimaryId($id)
    {
        return $this->fetchRow(array($this->getPrimary()." = ? "=>$id));
    }
    
    /**
     * 通过主键获取
     *
     * @return Zend_Db_Table_Row_Abstract
     */
    public function getByPrimaryIdForUpdate($id)
    {
        return $this->fetchRow($this->select()->forUpdate()->where($this->getPrimary()." = ? ", $id));
    }
    
    /**
     * 根据多个主键ID获取信息
     *
     * @param array $ids
     * @return
     */
    public function getListByPrimaryId($ids)
    {
        if (empty($ids)) return NULL;
        return $this->fetchAll(array($this->getPrimary()."  in (?) "=>$ids));
    }
    
    /**
     * 根据多个主键ID获取信息
     *
     * @param array $ids
     * @return
     */
    public function getListByPrimaryIdForUpdate($ids)
    {
    	if (empty($ids)) return NULL;
    	return $this->fetchAll($this->select()->forUpdate()->where($this->getPrimary()."  in (?) ", $ids));
    }
    
    /**
     * 根据多个主键ID获取信息，并按主键返回关联数组
     *
     * @param array $ids
     * @return array
     */
    public function getAssocListByPrimaryId($ids)
    {
        $list=$this->getListByPrimaryId($ids);
        //为空时直接返回
        if (!$list) return $list;
        
        $listArray=$list->toArray();
        if (!$listArray) {
            return $list;
        }
        
        $result=array();

        foreach ($listArray as $value){
            $result[@$value[$this->getPrimary()]]=$value;
        }
        
        return $result;
    }
    
    public function createRowObject($data)
    {
        if ($data){
            $rowData = array(
                    'table'   => $this,
                    'data'     => $data,
                    'readOnly' => false,
                    'stored'  => true
            );
            
            $rowClass = $this->getRowClass();
            if (!class_exists($rowClass)) {
                require_once 'Zend/Loader.php';
                Zend_Loader::loadClass($rowClass);
            }
            return new $rowClass($rowData);
        }else{
            return NULL;
        }
    }

    public function getPrimary()
    {
        if (is_array($this->_primary)){
            //大bug 必须赋值，不然影响下次
            $tmpPrimary=$this->_primary;
            return array_pop($tmpPrimary);
        }else{
            return $this->_primary;
        }
    }
    

    public function getlist(){
        $select = $this->_db->select();
        $select->from(array('p' => $this->_name));
        return $select;
    }
    
    public function addRow($ret){
        return $this->_db->insert($this->_name,$ret);
    }
    
    public function getOne($id){
        $id = (int)$id;
        $sql = "select * from $this->_name where $this->_primary = $id";
        $rows = $this->_db->fetchRow($sql);
        return  $rows;
    }
    
    public function updateRow($arr){
        return $this->_db->update($this->_name,$arr, array("$this->_primary = ? "=>(int)$arr[$this->_primary]));
    }
    
    public function deleteRow($id){
        return $this->_db->delete($this->_name,array("$this->_primary = ? "=>$id));
    }
    
    /**
     * 获取表名
     * @return string
     */
    public function getTableName(){
         return $this->_name;
    }
    
    /**
     * 异常报错后的日志保存
     */
    protected static function logExceptionInfo(Exception $e)
    {
        $log=DM_Module_Log::create(DM_Controller_Action::ERROR_LOG_SERVICE);
    
        $log->add("[IP".DM_Controller_Front::getInstance()->getClientIp()."]发现异常：".$e->getMessage(). PHP_EOL. "Params: ".json_encode(self::getRunParamsInfo())."\n". 'INFO: '.$e->getFile().'('.$e->getLine().')'. PHP_EOL. $e->getTraceAsString(). PHP_EOL);
    }
    
    private function getRunParamsInfo()
    {
        $info=DM_Controller_Front::getInstance()->getHttpRequest()->getParams();
        if (isset($info['error_handler'])){
            unset($info['error_handler']);
        }
        if (isset($_SERVER['REQUEST_URI'])){
            $info['uri']=$_SERVER['REQUEST_URI'];
        }
        if (isset($_SERVER['HTTP_REFERER'])){
            $info['referer']=$_SERVER['HTTP_REFERER'];
        }
    
        return $info;
    }
    
    /**
     * @return DM_Model_Table
     */
    public static function create()
    {
        return new static();
    }
}
