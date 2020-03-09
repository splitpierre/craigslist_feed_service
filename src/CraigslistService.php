<?php
class CraigslistService
{
    /**
     * Get Base URL
     * @return string
     */
    function getBaseUrl(){
        $server_host = $_SERVER['HTTP_HOST'] ?? null;
        if($server_host){
            $current_url = 'http://'.$server_host.dirname($_SERVER['PHP_SELF']).'/';
        } else {
            $current_url = 'http://c27f0133.ngrok.io/sportscarcurrent/feedservice/';
        }
        return $current_url;
    }

    /**
     * Get OPML feeds
     * @return array('name'=>...,'url'=>...)
     */
    function getOpmlList(){
        $opml_directory = scandir(__DIR__.'/../opml');
        unset($opml_directory[0]);
        unset($opml_directory[1]);
        $list = array();
        foreach ($opml_directory as $this_directory){
            $getOpml = CraigslistService::getUrlContents(CraigslistService::getBaseUrl() . "opml/".$this_directory);
            $xml = simplexml_load_string($getOpml);

//            var_dump(CraigslistService::getBaseUrl() . "opml/".$this_directory);
            foreach ($xml->body->outline as $item) {
                array_push($list,
                    array(
                        'name'=>$item->attributes()->title->__toString(),
                        'url'=>$item->attributes()->xmlUrl->__toString()
                    )
                );
            }
        }
        return $list;
    }

    /**
     * Get Downloaded Feeds Filenames
     * @return array
     */
    function getDownloadedFeeds(){
        $local_feeds_directory = scandir(__DIR__.'/../feeds');
        unset($local_feeds_directory[0]);
        unset($local_feeds_directory[1]);
        $list = array();
        foreach ($local_feeds_directory as $this_directory){
            array_push($list, $this_directory);
        }
        return $list;
    }

    /**
     * Count source and downloaded feeds
     * @return array('downloaded'=>..., 'all'=>...)
     */
    function countSync(){
        $downloaded_feeds = count((new CraigslistService())->getDownloadedFeeds());
        $all_source_feeds = count((new CraigslistService())->getOpmlList());
        return array('downloaded'=>$downloaded_feeds, 'all'=>$all_source_feeds);
    }

    /**
     * Get Contents from URL with CURL
     * Todo: Grab the response header code
     * @param $url
     * @return bool|string
     */
    function getUrlContents($url) {
//        $store_response_code = false;
//        if (strpos($url,'craigslist') !== false) {
//            $store_response_code = true;
//        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//        if($store_response_code){
//            CraigslistService::logResponse($httpcode);
//        }

        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }

    /**
     * Download One Random Feed (Existing ones excluded)
     * @return bool
     */
    function downloadRandomFeed(){
        $getDownloadedFeeds = (new CraigslistService())->getDownloadedFeeds();
        $downloadedFeeds = (!empty($getDownloadedFeeds) && is_array($getDownloadedFeeds)) ? $getDownloadedFeeds: array();
        $source_feeds = (new CraigslistService)->getOpmlList();


        //UNSET EXISTING FEEDS FROM SOURCE
        foreach ($source_feeds as $key=>$source_feed) {
            if(in_array($source_feed['name'] . '.xml', $downloadedFeeds)) {
                unset($source_feeds[$key]);
            }
        }
//        var_dump($source_feeds);
        $sync_counts = CraigslistService::countSync();

        $remaining_feeds = (int)$sync_counts['all']- (int)$sync_counts['downloaded'];
        $pickSingleFeed = rand(0,$remaining_feeds);
        $pickSingleFeed2 = rand(0,$remaining_feeds);
        $pickSingleFeed3 = rand(0,$remaining_feeds);
        $pickSingleFeed4 = rand(0,$remaining_feeds);
        $pickSingleFeed5 = rand(0,$remaining_feeds);

//        var_dump($source_feeds[$pickSingleFeed]['url']);
        $cl = (file_get_contents($source_feeds[$pickSingleFeed]['url'])) ?? null;
        $cl2 = (file_get_contents($source_feeds[$pickSingleFeed2]['url'])) ?? null;
        $cl3 = (file_get_contents($source_feeds[$pickSingleFeed3]['url'])) ?? null;
        $cl4 = (file_get_contents($source_feeds[$pickSingleFeed4]['url'])) ?? null;
        $cl5 = (file_get_contents($source_feeds[$pickSingleFeed5]['url'])) ?? null;

        $cl_xml = simplexml_load_string($cl);
        $cl2_xml = simplexml_load_string($cl2);
        $cl3_xml = simplexml_load_string($cl3);
        $cl4_xml = simplexml_load_string($cl4);
        $cl5_xml = simplexml_load_string($cl5);

        if($cl){
            $cl_xml->asXML(__DIR__.'/../feeds/'.$source_feeds[$pickSingleFeed]['name'].'.xml');
            sleep('3');
        }
        if($cl2){
            $cl2_xml->asXML(__DIR__.'/../feeds/'.$source_feeds[$pickSingleFeed2]['name'].'.xml');
            sleep('3');
        }
        if($cl3) {
            $cl3_xml->asXML(__DIR__ . '/../feeds/' . $source_feeds[$pickSingleFeed3]['name'] . '.xml');
            sleep('3');
        }
        if($cl4){
            $cl4_xml->asXML(__DIR__.'/../feeds/'.$source_feeds[$pickSingleFeed4]['name'].'.xml');
            sleep('3');
        }
        if($cl5){
            $cl5_xml->asXML(__DIR__.'/../feeds/'.$source_feeds[$pickSingleFeed5]['name'].'.xml');
        }


//        (new CraigslistService())->logFeedsDownloadTime($source_feeds[$pickSingleFeed]['name'].'.xml');
        CraigslistService::generateLocalOPML();
        return true;
    }

    /**
     * Generate Local OPML File
     */
    function generateLocalOPML(){
        $opml_header = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<opml version="1.0">'.PHP_EOL.'<head>'.PHP_EOL
            .'<title>Feeds exported by OPML Editor</title>'.PHP_EOL.'</head>'.PHP_EOL;
        $opml_content = '';
        $opml_footer = '</opml>';

        $feedList = (new CraigslistService)->getDownloadedFeeds();
        $opml_content .= '<body>'.PHP_EOL;
        foreach ($feedList as $feed_item){
            $opml_content .= '<outline title="'.$feed_item.'" text="'.$feed_item.'" xmlUrl="'.(new CraigslistService)->getBaseUrl().'feeds/'.$feed_item.'"/>'.PHP_EOL;
        }
        $opml_content .= '</body>'.PHP_EOL;
        unlink(__DIR__ . "/../opml_local.xml");
        $fp = fopen( __DIR__ . "/../opml_local.xml","wb");
        fwrite($fp,$opml_header.$opml_content.$opml_footer);
        fclose($fp);
    }


    /**
     * Remove All Local Feeds & Logs
     * @return bool
     */
    function resetFeedData(){
        $getDownloadedFeeds = (new CraigslistService())->getDownloadedFeeds();
        foreach ($getDownloadedFeeds as $feed){
            unlink(__DIR__.'/../feeds/'.$feed);
        }
        unlink(__DIR__.'/../cron_debug.txt');
        return true;
    }

    /**
     * Log Debug Data
     * @param null $param
     */
    function logDebug($param){
        if(!$param){
            $param = 'ERROR '.time();
        }
        $fp = fopen( __DIR__ . "/../cron_debug.txt","a");
        fwrite($fp,$param.PHP_EOL);
        fclose($fp);
    }

    /**
     * Get Debug Log
     * @return bool|string
     */
    function getDebugLog(){
        return CraigslistService::getUrlContents(CraigslistService::getBaseUrl() . "cron_debug.txt") ?? '';
    }

    /**
     * Log Time of Last Cron Run
     * @param null $param
     */
    function logCronTask($param = null){
        $content = time();

        if($param){$content = 'ERROR';}
        $fp = fopen( __DIR__ . "/../cron_logs.txt","wb");
        fwrite($fp,$content);
        fclose($fp);
    }

    /**
     * Get Last Time Cron Executed
     * @return false|string
     */
    function lastCronRun(){
        $fp = fopen( __DIR__ . "/../cron_logs.txt","r");
        if($fp){
            $time_ran = fread($fp,filesize("cron_logs.txt"));
            fclose($fp);
            return $time_ran;
        } else {return '--';}
    }

    /**
     * Todo: Log Feed file name and time on file
     * @param $feedName
     */
//    function logFeedsDownloadTime($feedName){
//        $name_time = '{"'.$feedName . '","'.time().'"}'.PHP_EOL;
//        $fp = fopen( __DIR__ . "/../feeds_time.txt","a");
//        fwrite($fp, $name_time);
//        fclose($fp);
//    }
//
    /**
     * Todo: Log Feed file name and time on file
     * @param $feedName
     */
//    function checkFeedTimeDelete($feedName){
//        $getFeedsLog = CraigslistService::getUrlContents(CraigslistService::getBaseUrl() . "feeds_time.txt") ?? '';
//        $handle = fopen(__DIR__ . "feeds_time.txt", "r");
//        if ($handle) {
//            while (($line = fgets($handle)) !== false) {
//                // process the line read.
//                var_dump($line);//todo:check each feed if matching one is older than 24 hrs and delete them if so
//            }
////            fclose($handle);
//        } else {
//            // error opening the file.
//        }
//    }

    /**
     * Todo: Log Last CL Response Code
     * @param null $param
     */
//    function logResponse($code){
//        $fp = fopen( __DIR__ . "/../last_response.txt","wb");
//        fwrite($fp,$code.PHP_EOL);
//        fclose($fp);
//    }
    /**
     * Get Last CL Response Code
     * @return bool|string
     */
//    function lastResponse(){
//        return CraigslistService::getUrlContents(CraigslistService::getBaseUrl() . "last_response.txt") ?? '';
//    }
}