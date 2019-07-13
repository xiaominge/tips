-- etc. KEYS[1] => user:hot:list
-- etc. user:hot:list => ["user:3:weight, user:5:weight user:8:weight user:36:weight user:55:weight]
-- 列表的值作为键保存数值类型的值
-- 将列表中所有值作为键的值，自增
local mincr_list = redis.call("lrange", KEYS[1], 0, -1)
local count = 0
for index,value in ipairs(mincr_list)
    do
        redis.call("incr", value)
        count = count + 1
end
return count
