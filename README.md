# php7-mongodb
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

新增 与yii1.1用法一样
$ad = new Aa();
$ad->id = 3;
$ad->fid = 35;
$ad->ctime = time();
$ad->save();
如插入失败 可用 $ad->getErrors();获取插入失败原因 采用yii原生rules字段验证

修改或删除 与yii1.1用法有区别
yii 支持 find/findall 查出数据再 update/updateall 或 delete/deleteall 回去
这里修改 只能 支持 update/updateall 方法内直接传参 接受三个参数 更新的数据(数组/对象),要更新哪些数据(数组/对象),其他选项(数组/对象)
这里删除 只能 支持 delete/deleteall 方法内直接传参  接受两个参数 删除条件(数组/对象) 其他选项
