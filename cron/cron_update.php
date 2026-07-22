<?php
/**
 * 定时更新脚本
 * 通过cron或系统计划任务定期执行
 * 
 * Linux Crontab示例（每6小时执行一次）:
 * 0 */6 * * * /usr/bin/php /path/to/php-migu/cron_update.php
 * 
 * Windows计划任务:
 * 使用schtasks命令或任务计划程序
 */

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../utils/utils.php';
require_once __DIR__ . '/updateData.php';

printBlue('===== 开始定时更新 =====');
printBlue('执行时间: ' . getDateTimeStr());

try {
    // 执行数据更新
    updateData(0);
    printGreen('===== 更新完成 =====');
} catch (Exception $e) {
    printRed('===== 更新失败: ' . $e->getMessage() . ' =====');
    exit(1);
}

exit(0);
