<?php
set_include_path(get_include_path() . PATH_SEPARATOR . 'phpseclib');
include_once __DIR__.'/../vendor/autoload.php';
$loader = new \Composer\Autoload\ClassLoader();
$loader->addPsr4('phpseclib\\', __DIR__ . '/path/to/phpseclib2.0');
$loader->register();

use phpseclib\Crypt\RSA;
use phpseclib\Net\SFTP;

class CraigslistService
{
    public $config;

    public function __construct() {
        $this->config = parse_ini_file(__DIR__.'/../config.ini', true, INI_SCANNER_RAW);
    }

    /**
     * Get Base URL
     * @return string
     */
    function getBaseUrl(){
        $server_host = $_SERVER['HTTP_HOST'] ?? null;
        if($server_host){
            $current_url = 'http://'.$server_host.dirname($_SERVER['PHP_SELF']).'/';
        } else {
            if($this->config['method'] === 'ngrok'){
                $current_url = $this->config['ngrok_url'].$this->config['base_url'];
            } else {
                $current_url = $this->config['localhost'].$this->config['base_url'];//defaults to localhost
            }
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
     * @param $url
     * @return bool|string
     */
    function getUrlContents($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }

    /**
     * Download X Feeds (Existing ones excluded)
     * @return bool
     */
    function downloadFeeds(){
        $getDownloadedFeeds = (new CraigslistService())->getDownloadedFeeds();
        $downloadedFeeds = (!empty($getDownloadedFeeds) && is_array($getDownloadedFeeds)) ? $getDownloadedFeeds: array();
        $source_feeds = (new CraigslistService)->getOpmlList();

        //UNSET EXISTING FEEDS FROM SOURCE
        foreach ($source_feeds as $key=>$source_feed) {
            if(in_array($source_feed['name'] . '.xml', $downloadedFeeds)) {
                unset($source_feeds[$key]);
            }
        }
        $source_feeds = array_values($source_feeds);//reset array keys

        //loop feeds_per_minute
        for ($i = 1; $i <= (int)$this->config['feeds_per_minute']; $i++) {
            $handle = @fopen($source_feeds[$i]['url'], 'r');

            if(!$handle){
                $feedUrl = $source_feeds[$i]['url'];
                if($feedUrl !== null){
                    (new CraigslistService)->logDebug(json_encode(array('issue_downloading_feed', date('Y-m-d_H-i-s', time()), $source_feeds[$i]['url'])));
                }
            }else{
                $cl = (file_get_contents($source_feeds[$i]['url'])) ?? null;
                $cl_xml = simplexml_load_string($cl);
                if($cl){
                    $cl_name = $source_feeds[$i]['name'].'.xml';
                    $cl_xml->asXML(__DIR__.'/../feeds/'.$cl_name);
                    CraigslistService::uploadFeedsToServer($cl_name);
                    sleep((int)$this->config['feeds_sleep_seconds']);
                }
            }
        }

        CraigslistService::generateLocalOPML();

        return true;
    }

    /**
     * Ping Craigslist
     * @return mixed
     */
    function pingCraigslist(){
        $source_feeds = (new CraigslistService)->getOpmlList();
        $pickRandom = rand(0,count($source_feeds));
        $url = $source_feeds[$pickRandom]['url'];
        $handle = curl_init($url);
        curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($handle);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);
        return $httpCode;
    }

    /**
     * Generate Local OPML File
     */
    function generateLocalOPML(){
        $key = new RSA();
        $sftp = new SFTP($this->config['sftp_server'], $this->config['sftp_port']);

        $key->setPassword($this->config['sftp_key_pass']);
        $key->loadKey(file_get_contents(CraigslistService::getBaseUrl().$this->config['sftp_key']));

        $opml_header = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<opml version="1.0">'.PHP_EOL.'<head>'.PHP_EOL
            .'<title>Feeds exported by OPML Editor</title>'.PHP_EOL.'</head>'.PHP_EOL;
        $opml_content = '';
        $opml_footer = '</opml>';

        $feedList = (new CraigslistService)->getDownloadedFeeds();
        $opml_content .= '<body>'.PHP_EOL;
        foreach ($feedList as $feed_item){
            $opml_content .= '<outline title="'.$feed_item.'" text="'.$feed_item.'" xmlUrl="'.$this->config['server_base'].'feeds/'.$feed_item.'"/>'.PHP_EOL;
        }
        $opml_content .= '</body>'.PHP_EOL;
        unlink(__DIR__ . "/../opml_local.xml");
        $fp = fopen( __DIR__ . "/../opml_local.xml","wb");
        fwrite($fp,$opml_header.$opml_content.$opml_footer);
        fclose($fp);

        //delete remote opml_local.xml
        if ($sftp->login($this->config['sftp_login'], $key) ) {
            $sftp->chdir($this->config['sftp_dir']);
            $sftp->delete('opml_local.xml', false);
            $sftp->chdir('..');
            $sftp->chdir('..');
            if($sftp->put($this->config['sftp_dir'].'/opml_local.xml', __DIR__.'/../opml_local.xml', SFTP::SOURCE_LOCAL_FILE)){
                (new CraigslistService)->logDebug(json_encode(array('sftp_opml_upload_success', date('Y-m-d_H-i-s', time()))));
            } else {
                (new CraigslistService)->logDebug(json_encode(array('sftp_opml_upload_failed', date('Y-m-d_H-i-s', time()))));
            }
        } else {
            (new CraigslistService)->logDebug(json_encode(array('sftp_connect_failed_gen_opml', date('Y-m-d_H-i-s', time()))));
            exit('Login Failed');
        }


    }

    /**
     * Upload Feeds into an online server with SFTP
     * @param $which
     * @return bool
     */
    function uploadFeedsToServer($which){
        $key = new RSA();
        $sftp = new SFTP($this->config['sftp_server'], $this->config['sftp_port']);

        $key->setPassword($this->config['sftp_key_pass']);
        $key->loadKey(file_get_contents(CraigslistService::getBaseUrl().$this->config['sftp_key']));

        if ($sftp->login($this->config['sftp_login'], $key) ) {
            // send all feeds
            (new CraigslistService)->logDebug(json_encode(array('sftp_connect_success', date('Y-m-d_H-i-s', time()))));

            if($which === 'all'){
                $downloaded_feeds = (new CraigslistService())->getDownloadedFeeds();

                foreach ($downloaded_feeds as $feed){
                    $sftp->put($this->config['sftp_dir'].'/feeds/'.$feed, __DIR__.'/../feeds/'.$feed, SFTP::SOURCE_LOCAL_FILE);
                }

                return true;
            } else {
                // send single feed
                $feed = $which;
                if($sftp->put($this->config['sftp_dir'].'/feeds/'.$feed, __DIR__.'/../feeds/'.$feed, SFTP::SOURCE_LOCAL_FILE)){
                    (new CraigslistService)->logDebug(json_encode(array('sftp_upload_feed_success', date('Y-m-d_H-i-s', time()), 'feed'=>$feed)));
                } else {
                    (new CraigslistService)->logDebug(json_encode(array('sftp_upload_feed_failed', date('Y-m-d_H-i-s', time()), 'feed'=>$feed)));
                }
            }
        } else {
            (new CraigslistService)->logDebug(json_encode(array('sftp_connect_failed', date('Y-m-d_H-i-s', time()))));
            exit('Login Failed');
        }
    }

    /**
     * Delete All Feeds From Live Server
     */
    function deleteFeedsFromServer(){
        $key = new RSA();
        $sftp = new SFTP($this->config['sftp_server'], $this->config['sftp_port']);

        $key->setPassword($this->config['sftp_key_pass']);
        $key->loadKey(file_get_contents(CraigslistService::getBaseUrl().$this->config['sftp_key']));

        if ($sftp->login($this->config['sftp_login'], $key) ) {
            $sftp->chdir($this->config['sftp_dir'].'/feeds');
            foreach ($sftp->nlist() as $file){
                if(strpos($file, 'xml')){
                    $sftp->delete($file, false);
                }
            }
            $sftp->chdir('..');
            $sftp->delete('opml_local.xml', false);
            (new CraigslistService)->logDebug(json_encode(array('sftp_feeds_deleted', date('Y-m-d_H-i-s', time()))));
        } else {
            (new CraigslistService)->logDebug(json_encode(array('sftp_connect_failed_del_feeds', date('Y-m-d_H-i-s', time()))));
            exit('Login Failed');
        }
    }

    /**
     * Remove All Feeds & Logs from local and online server
     * @return bool
     */
    function resetFeedData(){
        $getDownloadedFeeds = (new CraigslistService())->getDownloadedFeeds();
        foreach ($getDownloadedFeeds as $feed){
            unlink(__DIR__.'/../feeds/'.$feed);
        }
        $content ='';
        $fp = fopen( __DIR__ . "/../catch_errors","wb");
        $fp2 = fopen( __DIR__ . "/../cron_debug.txt","wb");
        fwrite($fp,$content);
        fwrite($fp2,$content);
        fclose($fp);
        fclose($fp2);
        if($this->config['method'] =='upload'){
            CraigslistService::deleteFeedsFromServer();
        }

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
     * @return bool[]|string[]
     */
    function getDebugLog(){
        return array(
            'cron_debug'=>CraigslistService::getUrlContents(CraigslistService::getBaseUrl() . "cron_debug.txt") ?? '',
            'catch_errors'=>CraigslistService::getUrlContents(CraigslistService::getBaseUrl() . "catch_errors") ?? ''
        );
    }

    /**
     * Log Time of Last Cron Run
     * @param null $param
     */
    function logCronTask($param = null){
        $content = time();

        if($param){$content = 'ERROR';}
        $fp = fopen( __DIR__ . "/../cron_last_time.txt","wb");
        fwrite($fp,$content);
        fclose($fp);
    }

    /**
     * Get Last Time Cron Executed
     * @return false|string
     */
    function lastCronRun(){
        if(file_exists(__DIR__ . "/../cron_last_time.txt")){
            $fp = fopen( __DIR__ . "/../cron_last_time.txt","r");
            $time_ran = fread($fp,filesize("cron_last_time.txt"));
            fclose($fp);
            return $time_ran;
        } else {
            return null;
        }
    }

    /**
     * Convert Minutes into Hour and Minutes
     * @param $time
     * @param string $format
     * @return string|void
     */
    function convertToHoursMins($time, $format = '%02d:%02d') {
        if ($time < 1) {
            return;
        }
        $hours = floor($time / 60);
        $minutes = ($time % 60);
        return sprintf($format, $hours, $minutes);
    }
}