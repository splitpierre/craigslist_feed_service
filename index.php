<?php
/**
 * GLOBAL / INCLUDES / DEFAULTS
 */
global $actionMessage, $messageClass;
include './src/CraigslistService.php';
include('./vendor/erusev/parsedown/Parsedown.php');

$actionMessage = '';
$messageClass = '';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$opml_directory = scandir('./opml');
unset($opml_directory[0]);
unset($opml_directory[1]);

/**
 * POST/GET REQUESTS
 */
$action = $_GET['unlink'] ?? '';
$success = $_GET['success'] ?? '';
$download = $_GET['download'] ?? '';

if($action){
    if(!unlink('./feeds/'.$action)){
        $actionMessage = "$action cannot be deleted due to an error";
        $messageClass = 'alert-danger';
    } else {
        header('Location: '. (new CraigslistService)->getBaseUrl() . '?success=true');
    }
}
if($success){
    $messageClass = 'alert-success';
    $actionMessage = "Success";
}
/**
 * TESTS
 */
//if($download == 'true'){
//    $downloaded_feeds = (new CraigslistService)->downloadRandomFeed();
//    if($downloaded_feeds){
//        header('Location: '. (new CraigslistService)->getBaseUrl() . '?success=true');
//    }
//}
(new CraigslistService)->generateLocalOPML();
//(new CraigslistService)->downloadRandomFeed();
//(new CraigslistService())->logCronTask('error');
?>

<html lang="en">
<head>
    <title>CRAIGSLIST FEED DOWNLOADER AND SERVICE</title>
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
                <a class="navbar-brand btn btn-secondary" href="<?php echo (new CraigslistService())->getBaseUrl(); ?>"><b><i class="fa fa-rss"></i> SPORTSCARCURRENT SERVICE</b></a>

                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarCollapse">

                    <div class="mt-2 mt-md-0 ml-auto">
                        <a href="<?php echo (new CraigslistService())->getBaseUrl(); ?>" class="btn btn-light btn-sm">
                            <i class="fa fa-clock-o"></i> <b>Last Cron:</b>
                            <?php
                            $last_run = (new CraigslistService())->lastCronRun();
                            echo ($last_run !== 'ERROR') ? date("F d, Y h:i:s", $last_run): $last_run;
                            ?>
                        </a>
                        <a href="<?php echo (new CraigslistService())->getBaseUrl(); ?>" class="btn btn-info btn-sm">
                            <?php $sync_counts= (new CraigslistService())->countSync();?>
                            <i class="fa fa-refresh"></i> <b>Sync: <?php echo $sync_counts['downloaded'];?>/<?php echo $sync_counts['all'];?>
                                | ETA:
                                <?php
                                /**
                                 * ATTENTION, ETA SHOULD BE DIVIDED BY HOW MANY FEEDS ARE DOWNLOADED PER MINUTE
                                */
                                $minutesRemaining = ((int)$sync_counts['all']-(int)$sync_counts['downloaded'])/5;
                                if($minutesRemaining > 60){
                                    $hours = $minutesRemaining/60;
                                    echo round($hours). ' Hour(s)';

                                }  else {
                                    echo $minutesRemaining. ' Min(s)';
                                }
                                ?>
                            </b>
                        </a>
                    </div>
                </div>
            </nav>
        </header>

        <main role="main" class="container-fluid p-3 mt-5">
            <?php
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
                                </ul>
                            </div>
                        </div>
                        <div class=" p-3">
                            <table id="opml_table" class="display" style="width:100%">
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
                                    <li>Make sure you setup your feed importer on your Drupal site to run with a safe distance (Recommended 12hrs), from this service feed reset/removal (midnight)</li>
                                </ul>
                            </div>
                        </div>
                        <div class=" p-3 ">
                            <table id="feeds_table" class="display" style="width:100%">
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
                            <div class="col-md-12">
                                <!--                        <h3>Debug Log:</h3>-->
                                <code>
                                    <?php echo (new CraigslistService())->getDebugLog();?>
                                </code>
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
            $('a[data-item]').on('click', function () {
                var r = confirm("Are you sure?");
                if (r == true) {
                    window.location.href = '<?php echo (new CraigslistService)->getBaseUrl();?>?unlink='+$(this).attr('data-item');
                }
            });
            $('#opml_table').DataTable();
            var feeds_table = $('#feeds_table').DataTable( {
                dom: 'Bfrtip',
                buttons: [
                    {
                        text: 'Download OPML',
                        action: function ( e, dt, node, config ) {
                            // alert( 'Button activated' );
                            window.location.href = '<?php echo (new CraigslistService)->getBaseUrl(); ?>opml_local.xml';
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
