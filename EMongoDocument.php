<?php
/**
 * Created by PhpStorm.
 * User: www
 * Date: 20-1-4
 * Time: 下午11:13
 */

/**
 * @property object $writeConcern
 */
abstract class EMongoDocument extends CModel{
    private		static		$_models		= array();
    private $_validators;  		// validators
    private $_errors=array();	// attribute name => array of errors
    protected static $_emongoDb;
    private $writeConcern;
    private $execute = null;
    private $iswritepk = false;
    const ASC = 1;
    const DESC = -1;
    public static $operators = array(
        'greater'		=> '$gt',
        '>'				=> '$gt',
        'greatereq'		=> '$gte',
        '>='			=> '$gte',
        'less'			=> '$lt',
        '<'				=> '$lt',
        'lesseq'		=> '$lte',
        '<='			=> '$lte',
        'noteq'			=> '$ne',
        '!='			=> '$ne',
        '<>'			=> '$ne',
        'in'			=> '$in',
        'notin'			=> '$nin',
        'all'			=> '$all',
        'size'			=> '$size',
        'type'			=> '$type',
        'exists'		=> '$exists',
        'notexists'		=> '$exists',
        'elemmatch'		=> '$elemMatch',
        'mod'			=> '$mod',
        '%'				=> '$mod',
        'equals'		=> '$eq',
        'eq'			=> '$eq',
        '=='			=> '$eq',
        'where'			=> '$where',
        'or'			=> '$or'
    );
    public function __construct($scenario='insert')
    {
        $this->getMongoDBComponent()->getConnection();
    }
    public function getMongoDBComponent()
    {
        if(self::$_emongoDb===null)
            self::$_emongoDb = Yii::app()->getComponent('mongodb');echo 'new';

        return self::$_emongoDb;
    }
    public static function model($className=__CLASS__)
    {
        if(isset(self::$_models[$className]))
            return self::$_models[$className];
        else
        {
            $model=self::$_models[$className]=new $className(null);
            return $model;
        }
    }
    public function getCollectionName(){}
    public function rules()
    {
        return array();
    }
    public function behaviors()
    {
        return array();
    }
    public function attributeLabels()
    {
        return array();
    }

    public function getAttributeLabel($attribute)
    {
        $labels=$this->attributeLabels();
        if(isset($labels[$attribute]))
            return $labels[$attribute];
        else
            return $this->generateAttributeLabel($attribute);
    }

    public function generateAttributeLabel($name)
    {
        return ucwords(trim(strtolower(str_replace(array('-','_','.'),' ',preg_replace('/(?<![A-Z])[A-Z]/', ' \0', $name)))));
    }

    public function addError($object,$attribute,$message='',$params=array())
    {
         $remsg =$this->getAttributeLabel($attribute);
        $this->addErr($object,$remsg);
    }

    public function addErr($attribute,$error)
    {
        $this->_errors[$attribute][]=$error;
    }

    public function clearErrors($attribute=null)
    {
        if($attribute===null)
            $this->_errors=array();
        else
            unset($this->_errors[$attribute]);
    }

    public function getErrors($attribute=null)
    {
        if($attribute===null)
            return $this->_errors;
        else
            return isset($this->_errors[$attribute]) ? $this->_errors[$attribute] : array();
    }

    public function attributeNames(){
        $arr = array();
        $class=new ReflectionClass(get_class($this));
        foreach($class->getProperties() as $property)
        {
            $name=$property->getName();
            if($property->isPublic() && !$property->isStatic())
                $arr[$name] = $this->$name;
        }
        if(!$this->iswritepk && array_key_exists('_id',$arr))
            unset($arr['_id']);
        return $arr;
    }

    public function validate($attributes=null, $clearErrors = true)
    {
        $this->clearErrors();
        foreach($this->getValidators() as $validator)
            $validator->validate($this,$attributes);

        return !$this->hasErrors();
    }

    public function hasErrors($attribute=null)
    {
        if($attribute===null)
            return $this->_errors!==array();
        else
            return isset($this->_errors[$attribute]);
    }

    public function getValidatorList()
    {
        if($this->_validators===null)
            $this->_validators=$this->createValidators();
        return $this->_validators;
    }
    public function getValidators($attribute=null)
    {
        if($this->_validators===null)
            $this->_validators=$this->createValidators();
        $validators=array();
        $scenario='';
        foreach($this->_validators as $validator)
        {
            if($validator->applyTo($scenario))
            {
                if($attribute===null || in_array($attribute,$validator->attributes,true))
                    $validators[]=$validator;
            }
        }
        return $validators;
    }
    public function createValidators()
    {
        $validators=new CList;
        foreach($this->rules() as $rule)
        {
            if(isset($rule[0],$rule[1]))  // attributes, validator name
                $validators->add(CValidator::createValidator($rule[1],$this,$rule[0],array_slice($rule,2)));
            else
                throw new CException(Yii::t('yii','{class} has an invalid validation rule. The rule must specify attributes to be validated and the validator name.',
                    array('{class}'=>get_class($this))));
        }
        return $validators;
    }

     private function getwriteConcern(){
         return new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 1000);//可选，修改确认
     }

    private function getexecute(){
         if($this->execute === null)
             $this->execute =  self::$_emongoDb->manager->executeBulkWrite(self::$_emongoDb->dbName.'.'.$this->getCollectionName(), self::$_emongoDb->bulk, $this->writeConcern); # 此函数只能调一次
         return $this->execute;
     }

    public function save(){
        if(!$this->validate())
            return false;
        self::$_emongoDb->bulk->insert($this->attributeNames());
        return  $this->getexecute()->isAcknowledged()?$this->getexecute()->getInsertedCount():false;
    }

    public function update($filter=array(),$attributes=array(),$updateOptions=array()){
        if(empty($filter) || empty($attributes))
            return false;
        $this->getquery($attributes,$updateOptions);
        self::$_emongoDb->bulk->update($attributes, ['$set' => $filter],$updateOptions); # multi 为true批量更新  false 单条更新(默认) upsert 更新时查询没有是否新插入一条(true/false) 默认false
        return  $this->getexecute()->isAcknowledged()?$this->getexecute()->getModifiedCount():false;
    }

    public function updateAll($filter=array(),$attributes=array(),$updateOptions=array()){
        $updateOptions['multi'] = true;
        return  $this->update($filter,$attributes,$updateOptions);
    }

    public function delete($filter,$deleteOptions=array('limit'=>true)){
        if(!array_key_exists('limit',$deleteOptions))
            $deleteOptions['limit'] = true;

        $this->getquery($filter,$deleteOptions);
        self::$_emongoDb->bulk->delete($filter,$deleteOptions); # limit true/false 默认为false   为false时删除所有匹配数据 true时只删除一条
        return  $this->getexecute()->isAcknowledged()?$this->getexecute()->getDeletedCount():false;
    }

    public function deleteAll($filter,$deleteOptions=array()){
        $deleteOptions['limit'] = false;
        return  $this->delete($filter,$deleteOptions);
    }

    public function find($filter=array(),$queryOptions=array('limit'=>1)){
        if(!array_key_exists('limit',$queryOptions))
            $queryOptions['limit'] = 1;
        $this->getquery($filter,$queryOptions);
        $query = new MongoDB\Driver\Query($filter, $queryOptions);#limit 查询条数 默认为0   为0时查询所有匹配数据
        $cursor = self::$_emongoDb->manager->executeQuery(self::$_emongoDb->dbName.'.'.$this->getCollectionName(), $query);
        $data = $cursor->toArray(); # 此函数只能调一次
        if($queryOptions['limit'] == 1)
            return isset($data)?$data[0]:false;
        else
            return isset($data)?$data:false;
    }

    public function findAll($filter=array(),$queryOptions=array()){
        if(!array_key_exists('limit',$queryOptions))
            $queryOptions['limit'] = 0;
        return $this->find($filter,$queryOptions);
    }

    private function getquery(&$filter,&$queryOptions){
        $zdarr = $this->attributeNames();
        if(is_object($filter)){
            $tmp = $filter;
            $filter = [];
            foreach ($tmp as $key=>$val){
                if(!array_key_exists($key,$zdarr)){
                    unset($filter->$key);
                    $key = strtolower($key);
                    if($key == 'order')
                        $queryOptions['sort'] = $val;
                    elseif(is_array($val))
                        $this->chuli($filter,$key,$val);
                    else
                        $queryOptions[$key] = $val;
                }else{
                    if (is_array($val)){
                        unset($filter->$key);
                        $this->chuli($filter,$key,$val);
                    }else{
                        $filter[$key] = $val;
                    }
                }
            }
        }
    }

    private function chuli(&$filter,$key,$val){
        foreach ($val as $k=>$v){
            if($k == '$or' || $k == 'or'){
                $filter['$or'] = $this->chuarr($key,$v);
            }elseif($k == '$in' || $k == 'in'){
                $filter[$key]['$in'] = is_array($v)?$v:[$v];
            }elseif (array_key_exists($k, self::$operators)) {
                $filter[$key] = array(self::$operators[$k] => $v);
            }elseif(in_array($k,self::$operators)){
                $filter[$key] = array($k => $v);
            }
        }
    }

    private function chuarr($key,$v){
        $tmp=[];
        if(is_array($v))
            foreach ($v as $value){
                $tmp[] = array($key=>$value);
            }
        else
            $tmp[] = array($key=>$v);
        return $tmp;
    }
}
