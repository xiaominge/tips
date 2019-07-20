<?php

/**
 * 生成 score
 * @param int $num
 * @return Generator
 */
function getScore($num = 100)
{
    for ($i = 1; $i <= $num; $i++) {
        usleep(50);
        $score = intval(microtime(true) * 10000);
        yield 'frank' . $i => $score;
    }
}

/**
 * 阻塞方式获取锁
 * @param $redis
 * @param $key
 * @param $uuid
 * @param int $waitTime
 * @return bool
 */
function getLock($redis, $key, $uuid, $waitTime = 10000)
{
    $isLock = \Redis\Lock::lock($redis, $key, $uuid);
    if ($isLock) {
        return $isLock;
    } else {
        usleep($waitTime);
        return getLock($redis, $key, $uuid, $waitTime);
    }
}

/**
 * 获取 redis 实例
 * @return bool|Redis
 */
function getRedis()
{
    $redis = new \Redis();
    if (!$redis->connect('127.0.0.1', 6379, 0)) {
        return false;
    } else {
        return $redis;
    }
}

/**
 * php 生成UUID
 * @param string $prefix 前缀
 * @return string
 */
function uuid_create($prefix = "")
{
    $str = md5(uniqid(mt_rand(), true));
    $uuid = substr($str, 0, 8) . '-';
    $uuid .= substr($str, 8, 4) . '-';
    $uuid .= substr($str, 12, 4) . '-';
    $uuid .= substr($str, 16, 4) . '-';
    $uuid .= substr($str, 20, 12);
    return $prefix . $uuid;
}

