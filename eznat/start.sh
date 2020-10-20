#!/bin/bash
echo "启动中";
fuser -k -n tcp  80
fuser -k -n tcp  443
fuser -k -n tcp  8888
fuser -k -n tcp  8889

nohup php channel_server.php restart > /dev/null 2>&1 &
nohup php database_map.php restart  > /dev/null 2>&1 &
sleep 2
nohup php server.php restart  > /dev/null 2>&1 &
sleep 2
nohup php database_map.php restart  > /dev/null 2>&1 &
echo "启动完成";
