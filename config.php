<?php
define('DS', DIRECTORY_SEPARATOR);
//日志目录
define('LOGS_PATH','/data/logs/project/stat/'.DS);
//andriod日志目录 以月为文件夹单位
$yearmonth=date('Ym');
define('ANDRIOD_PATH','/data/andriod/'.$yearmonth.DS);
define('BH_PATH','/data/behaviour/'.$yearmonth.DS);

include(__DIR__.'/wyxpdo.php');
include(__DIR__.'/function.php');


$dbconf=array('dbhost'=>'221.249.329.160','dbname'=>'mks_market','username'=>'YCmarket','password'=>'MF4wdgg4z','charset'=>'utf8');
$db=new WYXPDO($dbconf);

$redis = new Redis();
$redis->connect('127.0.0.1',6379);
#$redis->auth('mianfei123');
$redis->auth('zzs@zgj2015');
$redis->SELECT(6);

/**
 * 封装设置Redis缓存
 * @param $key
 * @param $data
 * @param $expire
 */
function setRedis($key,$data,$expire=0){

    global $redis;
    //-1永不过期
    if($expire == -1){

        $jsonData =  json_encode($data);

        $redis->set($key,$jsonData);
        return;
    }
    //过期时间
    if($expire==0){

        $expire = 600;

    }

    $jsonData =  json_encode($data);

    $redis->setex($key,$expire, $jsonData);
}


/**
 * 获取Redis缓存
 * @param $key
 * @return mixed
 */
 function getRedis($key, $bool = false){

    global $redis;

    $jsonData = $redis->get($key);

    return json_decode($jsonData,$bool);

}
?>
