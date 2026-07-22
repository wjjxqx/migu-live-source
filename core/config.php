<?php
/**
 * 咪咕视频爬取程序 - PHP版本
 * 配置文件
 */

// 配置文件路径
define('CONFIG_FILE', dirname(__DIR__) . '/data/config.json');

// 数据目录
define('DATA_DIR', dirname(__DIR__) . '/data');

// 接口文件路径
define('INTERFACE_TXT', DATA_DIR . '/interface.txt');
define('INTERFACE_M3U', DATA_DIR . '/interface.m3u');
define('INTERFACE_TXT_FORMAT', DATA_DIR . '/interfaceTXT.txt');
define('PLAYBACK_XML', DATA_DIR . '/playback.xml');

// 默认配置
$defaultConfig = [
    'userId' => '',           // 用户ID (可选，有会员可填写)
    'token' => '',            // 用户Token (可选，有会员可填写)
    'port' => 8080,           // 运行端口 (仅用于提示)
    'host' => '',             // 公网/自定义访问地址
    'rateType' => 3,          // 画质: 4蓝光(需VIP), 3高清, 2标清
    'pass' => '',             // 访问密码
    'enableHDR' => true,      // 是否开启HDR
    'enableH265' => true,     // 是否开启H265
    'updateInterval' => 6,    // 节目信息更新间隔(小时)
    'ignoreCategory' => '',   // 忽略分类，逗号分隔
    'mergeTVCategory' => true,// 是否合并TV分类
    'customMergeCategory' => '' // 自定义合并分类
];

/**
 * 获取配置 (支持环境变量覆盖，适配Render等云平台)
 */
function getConfig() {
    global $defaultConfig;
    
    // 环境变量优先 (Render部署时使用)
    $envConfig = [];
    if (getenv('MIGU_USER_ID')) $envConfig['userId'] = getenv('MIGU_USER_ID');
    if (getenv('MIGU_TOKEN')) $envConfig['token'] = getenv('MIGU_TOKEN');
    if (getenv('MIGU_RATE_TYPE')) $envConfig['rateType'] = intval(getenv('MIGU_RATE_TYPE'));
    if (getenv('MIGU_HOST')) $envConfig['host'] = getenv('MIGU_HOST');
    if (getenv('MIGU_PASS')) $envConfig['pass'] = getenv('MIGU_PASS');
    if (getenv('MIGU_ENABLE_HDR')) $envConfig['enableHDR'] = getenv('MIGU_ENABLE_HDR') === 'true';
    if (getenv('MIGU_ENABLE_H265')) $envConfig['enableH265'] = getenv('MIGU_ENABLE_H265') === 'true';
    if (getenv('MIGU_IGNORE_CATEGORY')) $envConfig['ignoreCategory'] = getenv('MIGU_IGNORE_CATEGORY');
    if (getenv('MIGU_MERGE_TV')) $envConfig['mergeTVCategory'] = getenv('MIGU_MERGE_TV') === 'true';
    
    // 文件配置 (本地部署时使用)
    $fileConfig = [];
    if (file_exists(CONFIG_FILE)) {
        $configJson = file_get_contents(CONFIG_FILE);
        $fileConfig = json_decode($configJson, true) ?? [];
    }
    
    // 优先级: 环境变量 > 文件配置 > 默认值
    return array_merge($defaultConfig, $fileConfig, $envConfig);
}

/**
 * 保存配置
 */
function saveConfig($config) {
    global $defaultConfig;
    
    // 确保数据目录存在
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0755, true);
    }
    
    // 只保存允许的字段
    $savedConfig = [];
    foreach ($defaultConfig as $key => $value) {
        $savedConfig[$key] = isset($config[$key]) ? $config[$key] : $value;
    }
    
    file_put_contents(CONFIG_FILE, json_encode($savedConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return $savedConfig;
}

/**
 * 获取配置项
 */
function getConf($key, $default = null) {
    $config = getConfig();
    return isset($config[$key]) ? $config[$key] : $default;
}
