<?php

namespace Redis;
/**
 *
 * redis 加锁 --单Redis实例实现分布式锁
 *
 * -- 分布式请使用：Redlock:https://github.com/ronnylt/redlock-php
 * -- 详情参考： http://www.redis.cn/topics/distlock.html
 *
 */
class Lock
{
    const LOCK_SUCCESS = true;
    const IF_NOT_EXISTS = 'NX';
    const MILLISECOND_EXPIRE_TIME = 'PX';
    const EXPIRE_TIME = 60000; // millisecond => 60s

    /**
     * 加锁
     * @param $redis
     * @param $key
     * @param $uuid
     * @param string $expireTime
     * @return bool
     */
    public static function lock($redis, $key, $uuid, $expireTime = '')
    {
        if (empty($expireTime)) {
            $expireTime = self::EXPIRE_TIME;
        }

        $result = $redis->set($key, $uuid, [self::IF_NOT_EXISTS, self::MILLISECOND_EXPIRE_TIME => $expireTime]);
        return self::LOCK_SUCCESS === $result;
    }

    /**
     * 解锁
     *
     * 参考： https://github.com/phpredis/phpredis/blob/develop/tests/RedisTest.php
     * @param $redis
     * @param $key
     * @param $uuid
     * @return mixed
     */
    public static function unlock($redis, $key, $uuid)
    {
        $lua = <<<EOT
if redis.call("get", KEYS[1]) == ARGV[1] then
    return redis.call("del", KEYS[1])
else
    return 0
end
EOT;
        $result = $redis->eval($lua, array($key, $uuid), 1);
        return $result;
    }
}
