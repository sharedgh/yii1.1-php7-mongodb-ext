# 自己造轮子php7 + yii1.1 mongodb扩展类 用法
PHP项目交流分享
配置文件加上:

在 components 数组内加配置如下
'mongodb' => array(
    'class'            => 'EMongoDB', //主文件
    'connectionString' => 'mongodb://root:123456@127.0.0.1:27017', //本地服务器地址
    'dbName'           => 'testdb',//数据库名称
),

扩展类目录extensions/mongodb文件目录清单: EMongoDB.php,EMongoDocument.php

用法: 在model类里面继承操作类(EMongoDocument) 就可以正常使用了
(注意 并不完全是yii1.1的用法 只是相似 mongodb 是区分数据类型的插入时用什么类型查询就要用什么类型不然查不出数据)

对象(object)用法:

1.查询
        $cri = new stdClass();
        $cri->id = 3;  # 查询字段值 (注意 mongodb查询是区分数据类型的)
        $cri->id = array('or'=>array(3,9));

        or用法 array('or'=>array(3,9)); 关联数组 or 的 value 是 数字|数组   查询id=3或id=9的数据

        in用法 array('in'=>array(3,9)); 与or用法一样  查询id包含3或9的数据

       大于(>=)/小于(<=) 用法 array('>='=>6); value 只能是 数字或数字字符串   查询大于等于6的数据

        and用法 $cri->id = 3;$cri->fid = 9; 查询 id=3 and fid=9 数据

        $cri->order = ['id'=>parent::DESC]; # 排序 倒序 (parent::DESC)/升序 (parent::ASC)  两个常量
        $cri->limit = 2;  # 查询条数 默认0表示没有限制 当用 find 方法查询 limit 不等于1时返回的数据格式是二维对象数组
        $cri->skip = 0; # 从什么地方开始查询
        $object = self::model()->find($cri);
        $object = self::model()->findall($cri);


2.新增
                $ad = new Aa();
                $ad->id = 3;
                $ad->fid = 35;
                $ad->ctime = time();
                $ad->save();
                插入错误用 $ad->getErrors(); 获取报错信息 采用 yii1.1 原生 rules 验证规则


3.删除
        $cri = new stdClass();
        $cri->id = 3;  # 查询字段值 (注意 mongodb查询是区分数据类型的)
        $cri->id = array('or'=>array(3,9));

        or用法 array('or'=>array(3,9)); 关联数组 or 的 value 是 数字|数组   查询id=3或id=9的数据

        in用法 array('in'=>array(3,9)); 与or用法一样  查询id包含3或9的数据

       大于(>=)/小于(<=) 用法 array('>='=>6); value 只能是 数字或数字字符串   查询大于等于6的数据

        and用法 $cri->id = 3;$cri->fid = 9; 查询 id=3 and fid=9 数据

        $cri->order = ['id'=>parent::DESC]; # 排序 倒序 (parent::DESC)/升序 (parent::ASC)  两个常量
        $cri->skip = 0; # 从什么地方开始查询
         self::model()->delete($cri);
         self::model()->deleteall($cri);


4.修改更新
               $cri = new stdClass();
               $cri->id = 3;  # 查询字段值 (注意 mongodb查询是区分数据类型的)
               $cri->id = array('or'=>array(3,9));

               or用法 array('or'=>array(3,9)); 关联数组 or 的 value 是 数字|数组   查询id=3或id=9的数据

               in用法 array('in'=>array(3,9)); 与or用法一样  查询id包含3或9的数据

              大于(>=)/小于(<=) 用法 array('>='=>6); value 只能是 数字或数字字符串   查询大于等于6的数据

               and用法 $cri->id = 3;$cri->fid = 9; 查询 id=3 and fid=9 数据

               $cri->order = ['id'=>parent::DESC]; # 排序 倒序 (parent::DESC)/升序 (parent::ASC)  两个常量
               $cri->skip = 0; # 从什么地方开始查询
                self::model()->update(array('id'=>9,'fid'=>345,'ctime'=>time()),$cri);  更新一条数据 参数一 要更新的数据(array) 参数二 更新条件对象|数组
                self::model()->updateAll(array('id'=>9,'fid'=>345,'ctime'=>time()),$cri);  更新所有匹配数据 参数一 要更新的数据(array) 参数二 更新条件对象|数组

数组(array)用法:

1.查询
          self::model()->find(array('id'=>3),array('limit'=>1));  # 查询一条id=3的数据 两个参数
          self::model()->find(array('id'=>3,'fid'=>9),array('limit'=>1)); # 查询一条id=3和fid=9的数据 相当与and
          self::model()->find(array('id'=>array('$gt'=>2)),array('limit'=>2));  # 查询两条id大于2的数据
          self::model()->find(array('id'=>array('$gt'=>2)),array('limit'=>2,'sort'=>array('id'=>parent::DESC))); # 按照倒序查询两条id大于2的数据
          self::model()->find(array('$or'=>array(array('id'=>9),array('id'=>3))),array('limit'=>2,'sort'=>array('id'=>parent::DESC))); # 按照倒序查询两条id等于3或id等于9的数据
          self::model()->find(array('id'=>array('$in'=>array(9,3))),array('limit'=>2,'sort'=>array('id'=>parent::DESC))); # 按照倒序查询两条id包含3或9的数据


2.新增
         目前暂不支持数组方式新增

3.删除
           self::model()->delete(array('id'=>3));  # 删除一条id=3的数据
           self::model()->deleteall(array('id'=>3));  # 删除所有id=3的数据
           self::model()->delete(array('id'=>3,'fid'=>9)); # 删除一条id=3和fid=9的数据 相当与and
           self::model()->deleteall(array('id'=>array('$gt'=>2)));  # 查询两条id大于2的数据
           self::model()->deleteall(array('id'=>array('$gt'=>2)),array('sort'=>array('id'=>parent::DESC))); # 按照倒序查询两条id大于2的数据
           self::model()->deleteall(array('$or'=>array(array('id'=>9),array('id'=>3))),array('sort'=>array('id'=>parent::DESC))); # 按照倒序查询两条id等于3或id等于9的数据
           self::model()->deleteall(array('id'=>array('$in'=>array(9,3))),array('sort'=>array('id'=>parent::DESC))); # 按照倒序查询两条id包含3或9的数据


4.修改更新
                 self::model()->update(array('id'=>9,'fid'=>345,'ctime'=>time()),array('id'=>3));  更新一条数据 参数一 要更新的数据(array) 参数二 更新条件对象|数组
                 self::model()->updateAll(array('id'=>9,'fid'=>345,'ctime'=>time()),array('id'=>3));  更新所有匹配数据 参数一 要更新的数据(array) 参数二 更新条件对象|数组
