<?php
require __DIR__ . "/../vendor/autoload.php";
require_once __DIR__. '/../common/event_const.php';

use Channel\Server as ChannelServer;
use Workerman\Worker;
use core\classes\Server;
use core\classes\ColorEcho;

if (!is_file(".env")) {
    ColorEcho::red(".env文件不存在， \r\n");
    ColorEcho::green("复制 .env.example 重命名为 .env。\r\n");
    ColorEcho::green("linux命令:  cp .env.example .env \r\n");
    die();
}
if (!is_file("map.json")) {
    ColorEcho::red("map.json 配置文件不存在. \r\n");
    ColorEcho::green("复制 map.example.json 重命名为  map.json。\r\n");
    ColorEcho::green("linux命令: cp map.example.json map.json \r\n");
    die();
}

Dotenv\Dotenv::createImmutable(__DIR__)->load();

$channelServer = new ChannelServer("0.0.0.0", env('CHANNEL_PORT'));
$mapInfo = file_get_contents(__DIR__ . '/map.json');

$data = json_decode($mapInfo, true);

if ($data == null || !isset($data['webMap']) || !isset($data['portMap'])) {
    ColorEcho::red("服务配置错误，请检查 map.json 配置\r\n");
    die();
}

if (env("HTTPS_MAP_SERVER_PORT") == "" || env("HTTPS_MAP_SERVER_PORT") == "" || env("HTTPS_MAP_SERVER_PORT") == "") {
    ColorEcho::red("服务配置错误，请复制 .env.example 文件进行修改 \r\n");
    die();
}


global $webMap, $portMap;

$webMap = $data['webMap'];
$portMap = $data['portMap'];
foreach ($portMap as $map) {
    $tmpServer = new Server("tcp://0.0.0.0:" . $map['remote_port']);
    $tmpServer->name = @$map['name'] ?: "未命名";
}
$web = new Server("\\core\\Protocols\\MyHttp://0.0.0.0:" . env("HTTP_MAP_SERVER_PORT"));
$web->name = "web";

$httpsDomain = exec("ls  cert | grep key", $keys);
$context = [
    'ssl' => [
        'verify_peer' => false,
        'SNI_enable' => true
    ]
];
foreach ($keys as $index => $key) {
    $domain = str_replace(".key", "", $key);
    $context['ssl']['local_cert'] = "./cert/{$domain}.pem";
    $context['ssl']['local_pk'] = "./cert/{$key}";
    $context['ssl']['SNI_server_certs'][$domain]['local_cert'] = "./cert/{$domain}.pem";
    $context['ssl']['SNI_server_certs'][$domain]['local_pk'] = "./cert/{$key}";
}
$httpsWeb = new Server("\\core\\Protocols\\MyHttp://0.0.0.0:" . env("HTTPS_MAP_SERVER_PORT"), $context);
$httpsWeb->transport = 'ssl';
$httpsWeb->name = "https_web";
Worker::runAll();
