<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/24
 * Time: 14:18
 *
 * 陈贵,王本东安排
 * 更新app评论
 *
 * 1千万的一个月4次,
 * 1百万-1千万的一个月2次
 * 10万-1百万2个月1次
 */
include("config.php");

//app入队列
//appQueue(200000, 100000);




$queueName="queue:app";

while (1){

    $json=$redis->LPop($queueName);

    if(empty($json))
    {
        debug_log($queueName.':暂无数据,退出','comment');

        break;
    }

    $app = json_decode($json,true);

    //debug_log($app,'comment');

    $appid = $app['id'];

    $downloads = $app['downloads'];

    $startMonth = '2016-01-01';

    for($i = 0; $i<=36; $i++){

        $count = getNum($downloads);

        for ($j = 0; $j < $count; $j++){

            $username = getUserName();#用户名

            $template = getComment();

            $content = is_array($template) ? $template['content'] : '。。。。';

            $ctime = strtotime(date('Y-m', strtotime("$startMonth +$i month")) .'-' . rand(1, 28));

            insertComment($appid, $username, $content, $ctime);
        }
        //echo date('Y-m-d', strtotime("$startMonth +$i month"))."\n";
    }
}

/**
 *
 * 从2016年1月份开始
 */
function getNum($downloads){

    switch ($downloads)
    {
        case $downloads > 10000000:
            return rand(1,4);
            break;
        case $downloads > 1000000 && $downloads < 10000000:
            return rand(1,2);
            break;
        case $downloads > 100000 && $downloads < 1000000:
            return rand(0,1);
            break;
        default:
            return 0;
    }

}

/**
 * 插入评论
 * @param $id
 * @param $downloads
 */
function insertComment($appid, $username, $content, $ctime){

    global $db;

    $db->exec("insert into app_comment(appid, sysid, username, content, ctime) values($appid, 666, '$username', '$content', $ctime)");
}

/**
 * 获取一条评论
 */
function getComment(){

    $template = getCommentTemplate();

    return $template[array_rand($template,1)] ;
}
/**
 *
 *获取评论模板
 *
 */
function getCommentTemplate(){

    global $db;

    $key = "getCommentTemplate";

    $data = getRedis($key, true);

    if(!$data) {

        $data = $db->getResults("select * from app_comment_templet");

       setRedis($key, $data);
    }
    return $data;
}

/**
 *
 * app同步入队列
 */
function appQueue($offset = 0, $limit = 500){

    global $db;

    global $redis;

    $key = "queue:app";

    $list = $db->getResults("select id,downloads from app_info limit $limit offset $offset");

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

