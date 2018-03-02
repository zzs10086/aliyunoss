<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/27
 * Time: 16:59
 */

require_once __DIR__ . '/autoload.php';
use OSS\OssClient;
use OSS\Core\OssException;

class Common
{
    const endpoint = 'oss-cn-shanghai-internal.aliyuncs.com';
    const accessKeyId = 'wmIuM5lYvnyusNOE07op';
    const accessKeySecret = 'WRLkUFZfXToChLW3pqh7WDcrYxtQlI0pl';
    const bucket = 'mfappf';

    /**
     * 根据Config配置，得到一个OssClient实例
     *
     * @return OssClient 一个OssClient实例
     */
    public static function getOssClient()
    {
        try {
            $ossClient = new OssClient(self::accessKeyId, self::accessKeySecret, self::endpoint, false);
        } catch (OssException $e) {
            printf(__FUNCTION__ . "creating OssClient instance: FAILED\n");
            printf($e->getMessage() . "\n");
            return null;
        }
        return $ossClient;
    }

    public static function getBucketName()
    {
        return self::bucket;
    }

    public static function println($message)
    {
        if (!empty($message)) {
            echo strval($message) . "\n";
        }
    }
}