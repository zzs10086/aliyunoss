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

$packagename = "eu.chainfire.supersu";
$apkurl ="http://app.mianfeiapp.com.cn/16/56fd57bc18942.apk";


$yybUrl = "http://sj.qq.com/myapp/detail.htm?apkName=" .$packagename;
$yybApkUrl = "http://imtt.dd.qq.com/16891/7F24B85431543508D139A145E38ED9D4.apk?fsname=eu.chainfire.supersu_2.82.1_282.apk&csr=1bbd";


getYybApkUrl($yybUrl);
exit;

$logoDownDir='/opt/apk/';

$apkname = end(explode('/', $apkurl));

$logoDownFile=$logoDownDir.$apkname;

download_remote_file_with_curl($yybApkUrl,$logoDownFile);

$object = str_replace('http://app.mianfeiapp.com.cn/','',$apkurl);


$bucket =Common::getBucketName();
$ossClient = Common::getOssClient();

if (is_null($ossClient)){
    echo 'ossclient error';
    exit(1);
}



$result = $ossClient->uploadFile($bucket, $object, $logoDownFile);
echo '<pre>';print_r($result);exit;

$content = "Hello, OSS!";
try {
    echo 'putObject'.'\n';
    $ossClient->putObject($bucket, '000/1.txt', $content);
} catch (OssException $e) {
    print $e->getMessage();
}



$doesExist = $ossClient->doesObjectExist($bucket, "000/00.apk");
echo  ($doesExist ? "yes" : "no");
exit;







//下载url地址的apk 到服务器
//download_remote_file_with_curl($url,$logoDownFile);

//服务器本地文件上传到阿里云oss

function getYybApkUrl($url){

    echo $url."\n";
    $content = curl_get_contents($url);
    echo $content;
    exit;
}

