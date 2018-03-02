<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/26
 * Time: 15:28
 *
 * 陈贵,王本东安排
 * 更新app包
 */
include("config.php");
require_once 'aliyun-oss-php-sdk-2.2.4/Common.php';

$queueName="queue:download";
//downloadQueue(0, 20000);
//exit;

$bucket =Common::getBucketName();

$ossClient = Common::getOssClient();

if (is_null($ossClient)){

    debug_log('ossClient error','download-error');
}

$logoDownDir='/opt/apk/';


while(1){

    $json=$redis->LPop($queueName);

    if(empty($json))
    {
        debug_log($queueName.':暂无数据,退出','download');

        break;
    }

    $app = json_decode($json,true);

    debug_log($app,'download');

    $packagename = $app['packagename'];

    $apkurl = $app['apkurl'];

    $yybApkUrl = $app['apkurl_yyb'];

    $newApkurl =  str_replace('.apk','_1.apk', $apkurl);

    $apkname = end(explode('/', $apkurl));

    $logoDownFile = $logoDownDir.$apkname;

    //下载应用宝apk到/opt/apk
    download_remote_file_with_curl($yybApkUrl,$logoDownFile);

    $object = str_replace('http://app.mianfeiapp.com.cn/','',$newApkurl);

    try{
        $result = $ossClient->uploadFile($bucket, $object, $logoDownFile);

        $filesize = filesize($logoDownFile);

        $time = time();

        $db->exec("update app_info set apkurl = '$newApkurl',update_status=1,apksize = $filesize, changed=$time where packagename = '$packagename' ");

    }catch (\OSS\Core\OssException $e){

        debug_log($e,'download-error');
    }

    //删除下载包
    exec("rm -rf  $logoDownFile");

}
//$packagename = "com.mrflapgcl";

//$apkurl ="http://app.mianfeiapp.com.cn/2015/08/06/55c2bf17b9a4d.apk";

//$yybApkUrl = "http://imtt.dd.qq.com/16891/F1FEDFD40BDA39161B4104EE5E0CB4ED.apk?fsname=com.mrflapgcl_1.2.0_289.apk&csr=1bbd";



/**
 *
 * app同步入队列
 */
function downloadQueue($offset = 0, $limit = 50000){

    global $db;

    global $redis;

    $key = "queue:download";

    $list = $db->getResults("select packagename,apkurl,apkurl_yyb from app_info where `status`=1 and biz_type=0 and update_status=0 and apkurl_yyb!='' limit $limit offset $offset");

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