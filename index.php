<?php
/**
 * GLOBAL / INCLUDES / DEFAULTS
 */
global $actionMessage, $messageClass;
include './src/CraigslistService.php';
include('./vendor/erusev/parsedown/Parsedown.php');
$config = parse_ini_file('config.ini', true, INI_SCANNER_RAW);
$actionMessage = '';
$messageClass = '';

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

$opml_directory = scandir('./opml');
unset($opml_directory[0]);
unset($opml_directory[1]);

/**
 * POST/GET REQUESTS
 */
$action = $_GET['unlink'] ?? '';
$success = $_GET['success'] ?? '';
$download = $_GET['download'] ?? '';
$delete = $_GET['reset'] ?? '';

if($action){
    if(!unlink('./feeds/'.$action)){
        $actionMessage = "$action cannot be deleted due to an error";
        $messageClass = 'alert-danger';
    } else {
        header('Location: '. (new CraigslistService)->getBaseUrl() . '?success=true');
    }
}
if($delete){
    (new CraigslistService())->resetFeedData();
    header('Location: '. (new CraigslistService)->getBaseUrl());
}
if($success){
    $messageClass = 'alert-success';
    $actionMessage = "Success";
}
/**
 * TESTS
 */
//(new CraigslistService)->generateLocalOPML();
//(new CraigslistService)->downloadRandomFeed();
//(new CraigslistService())->logCronTask('error');
?>

<html lang="en">
<head>
    <title><?php echo $config['service_name'] ?> - CRAIGSLIST FEED DOWNLOADER AND SERVICE</title>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- STYLESHEETS -->
    <link rel="stylesheet" href="//stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <link rel="stylesheet" href="//cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="//cdn.datatables.net/buttons/1.6.1/css/buttons.dataTables.min.css" crossorigin="anonymous">
    <link href="//stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">

</head>
<body class="bg-light">
    <div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">
        <header>
            <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
                <a class="navbar-brand btn btn-secondary" href="<?php echo ($config['method'] == 'upload') ? (new CraigslistService())->getBaseUrl() : $config['ngrok_url'].$config['base_url']; ?>"><b><i class="fa fa-rss"></i> <?php echo $config['service_name'] ?></b></a>

                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarCollapse">

                    <div class="mt-2 mt-md-0 ml-auto">
                        <a data-toggle="tooltip" title="Last Time Cron Job Ran" href="<?php echo ($config['method'] == 'upload') ? (new CraigslistService())->getBaseUrl() : $config['ngrok_url'].$config['base_url']; ?>" class="btn btn-light btn-sm">
                            <i class="fa fa-clock-o"></i>
                            <b>
                            <?php
                            $last_run = (new CraigslistService())->lastCronRun();
                            if($last_run !== null){
                                echo  date("Y/m/d h:i:s", $last_run);
                            } else {
                                echo 'NA';
                            }
                            ?>
                            </b>
                        </a>
                        <?php $sync_counts = (new CraigslistService())->countSync();?>
                        <?php $sync_class = ($sync_counts['downloaded'] == $sync_counts['all']) ? 'success' : 'info'; ?>
                        <a data-toggle="tooltip" title="Feeds Download Progress - The ETA might never
                        finish if there are inconsistencies on the feed file/format/syntax."
                           href="<?php echo ($config['method'] == 'upload') ? (new CraigslistService())->getBaseUrl() : $config['ngrok_url'].$config['base_url']; ?>"
                           class="btn btn-<?php echo $sync_class; ?> btn-sm">

                            <i class="fa fa-refresh"></i> <b>Sync: <?php echo $sync_counts['downloaded'];?>/<?php echo $sync_counts['all'];?>
                                <?php
                                $remaining_feeds = (int)$sync_counts['all'] - (int)$sync_counts['downloaded'];
                                $minutesRemaining = ($remaining_feeds*(int)$config['cron_min_interval'])/(int)$config['feeds_per_minute'];
                                if($minutesRemaining > 0){
                                    ?>
                                    | ETA:
                                    <?php
                                    echo (new CraigslistService())->convertToHoursMins($minutesRemaining, '%02d hours %02d minutes'); //
                                }

                                ?>
                            </b>
                        </a>



                        <?php
                        $ping_cl = (new CraigslistService())->pingCraigslist();
                        if($ping_cl == 403 || $ping_cl == 404){
                            $class = 'danger';
                            $icon = 'exclamation-triangle';
                        } elseif ($ping_cl == 200 || $ping_cl == 301){
                            $class = 'success';
                            $icon = 'check';
                        } else {
                            $class = 'warning';
                            $icon = 'question';
                        }
                        ?>
                        <a data-toggle="tooltip" title="Craigslist HTTP Response Code (Checks random feed from OPML list)"
                           href="<?php echo (new CraigslistService())->getBaseUrl(); ?>" class="btn btn-<?php echo $class;?> btn-sm">
                            <?php echo $ping_cl;?>
                            <i class="fa fa-<?php echo $icon; ?>"></i>
                            <b>
                            </b>
                        </a>

                        <a data-toggle="tooltip" title="FULL RESET - This will delete all the downloaded XML/OPML feeds and logs from both localhost and live server" data-delete-all="true"
                           href="javascript:void(0)" class="btn btn-danger btn-md">
                            <i class="fa fa-trash"></i></a>
                    </div>
                </div>
            </nav>
        </header>

        <main role="main" class="container-fluid p-3 mt-5">
            <?php
//            (new CraigslistService())->deleteFeedsFromServer();

            if($actionMessage){
                ?>
                <div class="alert <?php echo $messageClass;?>">
                    <?php echo $actionMessage;?>
                </div>
                <?php
            }
            if((int)(new CraigslistService())->countSync()['all'] > 8600){
                ?>
                <div class="alert alert-danger">
                    <b>ALERT: This script is limited to run 1 feed per minute totalling 8640 feeds per day. Consider downloading 5 feeds per minute if you need to increase this.
                    </b>
                </div>
                <?php
            }
            ?>
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="source-tab" data-toggle="tab" href="#source" role="tab" aria-controls="source" aria-selected="true">
                        OPML SOURCE FEEDS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link " id="local-tab" data-toggle="tab" href="#local" role="tab" aria-controls="local"
                       aria-selected="false">LOCAL FEEDS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="debug-tab" data-toggle="tab" href="#debug" role="tab" aria-controls="debug"
                       aria-selected="false">DEBUG</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="info-tab" data-toggle="tab" href="#info" role="tab" aria-controls="info"
                       aria-selected="false">README.md</a>
                </li>
            </ul>
            <div class="tab-content " id="myTabContent">
                <div class="tab-pane  fade show active" id="source" role="tabpanel" aria-labelledby="source-tab">
                    <div class="container-fluid border-bottom border-left border-right bg-white">
                        <div class=" p-3 ">
                            <div class="alert alert-info mt-2 w-100" role="alert">
                                <ul>
                                    <li>Make sure you only add .opml files generated with OPML Editor.</li>
                                    <li>Those are the list of feeds from all .opml files inside /opml folder.</li>
                                    <li>The .opml file names cannot contain spaces or special characters</li>
                                </ul>
                            </div>
                        </div>
                        <div class=" p-3">
                            <table id="opml_table" class="display responsive" style="width:100%">
                                <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>URL</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $opml_feed = (new CraigslistService)->getOpmlList();
                                foreach ($opml_feed as $opml_feed_item) {
                                    echo '<tr>
                        <td><a class="btn btn-light w-100" href="'.$opml_feed_item['url'].'" target="_blank"><i class="fa fa-eye"></i> '.$opml_feed_item['name'].'</a></td>
                        <td>'.$opml_feed_item['url'].'</td>
                        </tr>';
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>

                    </div>

                </div>
                <div class="tab-pane fade" id="local" role="tabpanel" aria-labelledby="local-tab">
                    <div class="container-fluid border-bottom border-left border-right bg-white">
                        <div class=" p-3 ">
                            <div class="alert alert-info mt-3" role="alert">
                                <ul>
                                    <li>By downloading the OPML bellow, you will get the OPML with all local feeds to use on Drupal.</li>
                                    <li>Make sure you setup your feed importer on your Drupal site to run with a safe distance (Recommended +6hrs), from this service feed automatic reset/removal (midnight)</li>
                                </ul>
                            </div>
                        </div>
                        <div class=" p-3 ">
                            <table id="feeds_table" class="display responsive" style="width:100%">
                                <thead>
                                <tr>
                                    <th>URL</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $feed = (new CraigslistService)->getDownloadedFeeds();
                                //                    var_dump($feed);
                                foreach ($feed as $feed_item){
                                    echo '<tr>
                            <td><a class="btn btn-light w-100" target="_blank" href="'. (new CraigslistService)->getBaseUrl().'feeds/'.$feed_item.'">
                            <i class="fa fa-eye"></i> '.(new CraigslistService)->getBaseUrl().$feed_item.'</a>
                            </td>
                            <td><a  href="javascript:void(0);" data-item="'.$feed_item.'" class="btn btn-danger w-100"><i class="fa fa-trash"></i> Delete</a></td>
                            </tr>';
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>


                </div>
                <div class="tab-pane fade" id="debug" role="tabpanel" aria-labelledby="debug-tab">

                    <div class="container-fluid border-bottom border-left border-right bg-white">
                        <div class="row p-3 ">
                            <div class="col-md-6" style="max-height: 700px;overflow-y: scroll;">
                                <h3>Debug Log:</h3>
                                <ul class="">
                                    <?php
                                    $explode = explode(PHP_EOL, (string)(new CraigslistService())->getDebugLog()['cron_debug']);
                                    foreach (array_reverse($explode) as $line){
                                        if(strpos($line, 'success')){
                                            echo '<li class="text-success">'.$line.'</li>';
                                        } else {
                                            echo '<li class="text-danger">'.$line.'</li>';
                                        }
                                    }
                                    ?>
                                </ul>
                            </div>
                            <div class="col-md-6"  style="max-height: 700px;overflow-y: scroll;">
                                <h3>Last Errors Thrown by Cron Task:</h3>
                                <?php
                                print_r((new CraigslistService())->getDebugLog()['catch_errors']);
                                ?>
                            </div>
                        </div>
                    </div>



                </div>
                <div class="tab-pane fade" id="info" role="tabpanel" aria-labelledby="info-tab">
                    <div class="container-fluid border-bottom border-left border-right bg-white">
                        <div class=" p-3 ">
                            <?php
                            $contents = file_get_contents(__DIR__.'/README.md');
                            $Parsedown = new Parsedown();
                            echo $Parsedown->text($contents);
                            ?>
                        </div>
                    </div>

                </div>

            </div>
            <div class="container-fluid" style="max-height: 300px;height:300px;overflow: auto;">

                <div class="row">
                    <div class="col-md-6 card bg-light text-danger p-3">
                        <?php
                        $downloaded_feeds = (new CraigslistService())->getDownloadedFeeds();
                        $downloadedFeeds = (!empty($downloaded_feeds) && is_array($downloaded_feeds)) ? $downloaded_feeds: array();
                        $source_feeds = (new CraigslistService())->getOpmlList();
                        foreach ($source_feeds as $key=>$source_feed) {
                            if(in_array($source_feed['name'].'.xml' , $downloadedFeeds)) {
                                unset($source_feeds[$key]);
                            }
                        }
                        ?>
                        <?php  if(count($source_feeds) > 0){
                           ?>
                            <h3>
                                Remaining feeds (<?php echo $sync_counts['all']-$sync_counts['downloaded'];?>):
                            </h3>
                            <div class="alert alert-warning">
                                <h5>Common Causes</h5>
                                <p>If the sync doesn't finish and you see feeds bellow, they likely have some kind of issue:</p>
                                <ul>
                                    <li>Check if URL of the feed on the OPML is outdated</li>
                                    <li>Check if it does a redirection (Eg.: from http://sfbayarea.craigslist.org it goes to https://sfbay.craigslist.org/).</li>
                                    <li>Check if xml is valid</li>
                                </ul>
                            </div>
                            <?php

                            foreach ($source_feeds as $source_key=>$source_feed) {
                                echo $source_key . ' - ' . $source_feed['name'].'<br>';
                            }
                        } else {
                            ?>
                            <div class="alert alert-success">
                                <h1 class="text-center"><i class="fa fa-thumbs-up"></i></h1>
                                <h5 class="text-center">Feeds are in sync!</h5>
                            </div>
                        <?php
                        }

                        ?>

                    </div>
                    <div class="col-md-6 card bg-dark text-success p-3">
                        <h3 data-toggle="tooltip" title="System Variables">System Variables</h3>
                        <ul>
                            <li><b>Service Method: </b><?php echo $config['method']; ?></li>
                            <li><b>Base URL: </b><?php echo $config['base_url']; ?></li>
                            <li><b>NGROK URL: </b><?php echo $config['ngrok_url']; ?></li>
                            <li><b>Localhost:</b> <?php echo $config['localhost']; ?></li>
                            <li><b>Download Feeds per Minute:</b> <?php echo $config['feeds_per_minute']; ?> /min</li>
                            <li><b>Sleep Between Downloads:</b> <?php echo $config['feeds_sleep_seconds']; ?> sec(s)</li>
                            <li><b>Cron Download Interval: </b>At every <?php echo $config['cron_min_interval']; ?> min</li>
                            <li><b>Cron Reset Interval: </b>At minute 0 past every 6th hour</li>
                            <li><b>SFTP Server Base:</b> <?php echo $config['server_base']; ?></li>
                            <li><b>SFTP Base Directory:</b> <?php echo $config['sftp_dir']; ?></li>
                            <li><b>SFTP HOST:PORT:</b> <?php echo $config['sftp_server']; ?>:<?php echo $config['sftp_port']; ?></li>
                            <li><b>SFTP Username: </b><?php echo $config['sftp_login']; ?></li>
                            <li><b>SFTP Key Passphrase: </b>**************</li>
                            <li><b>SFTP RSA Key Name:</b> <?php echo $config['sftp_key']; ?></li>

                        </ul>

                    </div>
                </div>



            </div>
        </main>

        <footer class="mastfoot mt-auto">
            <div class="inner">
                <samp>Developed w/ ❤ by Pierre Maciel from WeBizz.biz</samp>
            </div>
        </footer>
    </div>

    <!-- SCRIPTS -->
    <script src="//code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="//stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
    <script src="//cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js" crossorigin="anonymous"></script>
    <script src="//cdn.datatables.net/buttons/1.6.1/js/dataTables.buttons.min.js" crossorigin="anonymous"></script>
    <script>
        $(document).ready( function () {
            $(function () {
                $('[data-toggle="tooltip"]').tooltip()
            })
            $('a[data-delete-all]').on('click', function () {
                var r = confirm("Are you sure you want to continue?" +
                    "\n\nIt's safe to continue, it will delete all LOCAL FEEDS entries, delete generated local OPML and clear debug logs.");
                if (r == true) {
                    window.location.href = '<?php echo (new CraigslistService)->getBaseUrl();?>?reset='+$(this).attr('data-delete-all');
                }
            });
            $('a[data-item]').on('click', function () {
                var r = confirm("Are you sure?");
                if (r == true) {
                    window.location.href = '<?php echo (new CraigslistService)->getBaseUrl();?>?unlink='+$(this).attr('data-item');
                }
            });
            $('#opml_table').DataTable({responsive: true,scroller:true, scrollY:400});
            var feeds_table = $('#feeds_table').DataTable( {
                responsive: true,
                scroller: true,
                scrollY: 400,
                dom: 'Bfrtip',
                buttons: [
                    {
                        text: 'Download OPML',
                        action: function ( e, dt, node, config ) {
                            // alert( 'Button activated' );
                            window.location.href = '<?php echo $config['server_base']; ?>opml_local.xml';
                        }
                    }
                ]
            });
            feeds_table.buttons().container()
                .appendTo( $('.col-sm-6:eq(0)', feeds_table.table().container() ) );
        } );
    </script>
</body>
</html>
