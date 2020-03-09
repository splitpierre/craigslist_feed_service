<?php
include_once __DIR__.'/src/CraigslistService.php';

if((new CraigslistService())->downloadRandomFeed()){
    (new CraigslistService())->logCronTask();
    (new CraigslistService)->logDebug(json_encode(array('cron_task_success', date('Y-m-d H:i:s', time()))));

} else {
    (new CraigslistService())->logCronTask('error');
    (new CraigslistService)->logDebug(json_encode(array('cron_task_failed', date('Y-m-d H:i:s', time()))));
}

?>
