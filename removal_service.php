<?php
include_once __DIR__.'/src/CraigslistService.php';

(new CraigslistService())->resetFeedData();
(new CraigslistService)->logDebug(json_encode(array('cron_task_removal', date('Y-m-d H:i:s', time()))));

?>
