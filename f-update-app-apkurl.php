<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/28
 * Time: 17:04
 * 陈贵,王本东安排
 * 更新app包
 */
include("config.php");

//app入队列
//apkQueue(0, 100000);
//exit;

$queueName="queue:apkurl";

while(1){

    $json=$redis->LPop($queueName);

    if(empty($json))
    {
        debug_log($queueName.':暂无数据,退出','apkurl');

        break;
    }

    $app = json_decode($json,true);

    $pkgname = $app['packagename'];
    debug_log($pkgname,'apkurl');
    //$pkgname = 'com.snda.wifilocating';

    $yybUrl = "http://sj.qq.com/myapp/detail.htm?apkName=".$pkgname;

    $yybApkurl = getYybApk($yybUrl);

    //保存数据库
    if($yybApkurl){


        $db->exec("update app_info set apkurl_yyb = '$yybApkurl' where packagename = '$pkgname' ");
    }


    usleep(100000);

}

function getYybApk($yybUrl){

    $content = curl_get_contents($yybUrl);

    preg_match('/data-apkUrl=\"(.*?)\"/is',$content,$m);

    $apkurl = '';

    if(isset($m[1]) && !empty($m[1])){

        $apkurl = trim($m[1]);
    }

    return $apkurl;

}

/**
 *
 * app同步入队列
 */
function apkQueue($offset = 0, $limit = 50000){

    global $db;

    global $redis;

    $key = "queue:apkurl";

    $list = $db->getResults("select packagename from app_info limit $limit offset $offset");

    if(!$list) return;


    foreach ($list as $k=>$v){

        try
        {
            $redis->rPush($key,json_encode($v,JSON_UNESCAPED_UNICODE));
        }
        catch(Exception $e)
        {
            debug_log($e,'error');
        }
    }


}