<?php
ini_set("memory_limit", '1024M');
require_once "./func.php";
require_once "./Lock.php";
define('NL', PHP_SAPI == 'cli' ? PHP_EOL : "<br>");

//testLock();
//testLua();
testZset();

function testLock()
{
    $redis = getRedis();
    if (false === $redis) {
        echo '连接失败!' . NL;
        return;
    }

    $key = "www:lock:single";
    $uuid = uuid_create();
    getLock($redis, $key, $uuid);
    echo "执行业务代码" . NL;
    sleep(10);
    $r = \Redis\Lock::unlock($redis, $key, $uuid);
    if ($r) {
        echo '解锁!' . NL;
    } else {
        var_dump($r);
    }
}

function testLua()
{
    if (false === ($redis = getRedis())) {
        echo '连接失败!' . NL;
        return;
    }

    $key = "user:hot:list";
    $redis->del($key);
    $llen = $redis->lLen($key);
    $l = $redis->lRange($key, 0, -1);

    $listVal = ["user:3:weight", "user:5:weight", "user:15:weight"];
    array_unshift($listVal, $key);
    $r = call_user_func_array([$redis, "rPush"], $listVal);
    array_shift($listVal);
    array_map(function ($val) use ($redis) {
        $redis->set($val, 9);
    }, $listVal);

    $sha1Hash = $redis->get('$sha1Hash');
    echo $sha1Hash . NL;
    if (empty($sha1Hash)) {
        $luaScript = realpath('./list_mincr.lua');
        $luaScript = file_get_contents($luaScript);
        $sha1Hash = $redis->script('load', $luaScript);
        $redis->set('$sha1Hash', $sha1Hash);
        if ($sha1Hash) {
            echo 'Lua 脚本加载成功!' . NL;
        } else {
            echo 'Lua 脚本加载失败!' . NL;
            return;
        }
    }

    $redis->evalSha($sha1Hash, [$key], 1);
}

function testZset()
{
    $redis = getRedis();
    if (false === $redis) {
        echo '连接失败!' . NL;
        return;
    }

    $key = "user:notice";
    $num = 999999;

    $zSize = $redis->zCard($key);
    if ($zSize === 0 || $zSize < $num) {
        $scoreGen = getScore($num);
        // 生成器、节约内存
        foreach ($scoreGen as $user => $score) {
            $redis->zAdd($key, $score, $user);
        }
    }

    // zRangeByScore
    $start = intval(microtime(true) * 100000);
    $noticeArr = $redis->zRangeByScore($key, '-inf', '+inf', [
        'withscores' => 1,
        'limit' => [0, $num],
    ]);
    $end = intval(microtime(true) * 100000);
    echo ($end - $start) . PHP_EOL;

    // zScan
    $start = intval(microtime(true) * 100000);
    $iterator = null;
    $redis->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);
    while ($notice = $redis->zScan($key, $iterator, '*frank*', '3000')) {

    }
    $end = intval(microtime(true) * 100000);
    echo ($end - $start) . PHP_EOL;
}