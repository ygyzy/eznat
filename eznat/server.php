<?php
require_once 'init_laravel_orm.php';

use Workerman\Worker;

use core\Manage;
use core\classes\Server;

use App\Model\PortMap;
use App\Model\Client;

Client::where('id', '>', 0)->update(['is_online' => 0]); # 重置客户端状态为下线
PortMap::where('id', '>', 0)->update(['is_online' => 0]); # 重置服务端状态为下线

$data = PortMap::all();
foreach ($data as $port) {
    if ($port['remote_port'] == env("HTTP_MAP_SERVER_PORT") || $port['remote_port'] == env("HTTPS_MAP_SERVER_PORT")) {
        continue;
    }
    Manage::generateScriptFile($port);
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
// config example
/*$context = array(
    'ssl' => array(
        'local_cert'  => './cert/eznat.istiny.cc.pem',
        'local_pk'    => './cert/eznat.istiny.cc.key',
        'verify_peer' => false,
        'SNI_enable' => true,
        "SNI_server_certs" => [
            "wine.istiny.cc" => [
                'local_cert'  => './cert/wine.istiny.cc.pem',
                'local_pk'    => './cert/wine.istiny.cc.key'
            ]
        ]
    )
);*/
$httpsWeb = new Server("\\core\\Protocols\\MyHttp://0.0.0.0:" . env("HTTPS_MAP_SERVER_PORT"), $context);
$httpsWeb->transport = 'ssl';
$httpsWeb->name = "https_web";

Worker::runAll();
