#CRAIGSLIST FEED SERVICE
#### Motivation:
Craigslist will block/forbid (403 Forbidden) any kind of automated requests to 
its rss feeds, either you use proxies, VPS, request it from a web-server, or try to do any kind of automated/bulk requests on their feeds, therefore, we can't effectively consume craigslist feeds.
#### Solution:
With this in mind, this script will help you to download a list of craigslist feeds locally on a physical machine, and to service it to the web.
This way you can take advantage of using a Dynamic IP addresses from a Physical Machine to do bulk requests (which are less likely to get blocked) and then service those feeds to your site with an OPML file.
#### How it works:
You add OPML file(s) to /opml/ directory containing all feeds you want to service and that's it.
You'll be able to use the web interface to take a look at source feeds, and download an OPML generated locally containing all craigslist feeds locally stored.
Then you can link to this OPML file on a feed importer like Drupal feeds importer and fire requests at will.

##SETUP GUIDE
This is a Guide on how to set up and run this service.

1. Setup a [WAMP](http://www.wampserver.com/en/) server (Windows) or a web-server on linux & make it run on startup;
2. Put this project on the web directory (Eg.: /var/www/html/craigslist_feed_service OR C:/wamp/www/craigslist_feed_service);
3. Setup a cron job to execute http://localhost/craigslist_feed_service/service.php every minute and the removal at midnight;
    - */1 * * * * /bin/bash -c ". ~/.bashrc; php /var/www/html/craigslist_feed_service/service.php"
    - 0 0 * * * /bin/bash -c ". ~/.bashrc; php /var/www/html/craigslist_feed_service/removal_service.php"
4. Access http://localhost/craigslist_feed_service/ to view the interface;
5. Either use ngrok/pagekit/similar to provide this application to the internet, or setup virtualhost to do so manually and run on startup; 

Now your can consume craigslist feeds from your local machine without getting blocked/forbidden by Craigslist.

### Observations
- This script downloads 3 feeds per minute
- This script was made to execute on an OPML file generated with OPML Editor, please view the file inside /opml/ to understand expected xml structure;
- This also will use the outline title attribute to set feed file names, so it needs to exist;
- This script was made to service a Drupal website using Feeds module https://www.drupal.org/project/feeds/
- Depending on how many feeds you'll fetch from Craigslist, you can tweak downloadRandomFeed() to loop and download more feeds at once;

### Useful Links
- http://www.wampserver.com/en/ - Windows PHP Web Server
- https://www.duckdns.org/ - Free Dynamic DNS
- https://ngrok.com/ - Secure Introspect Tunnels to localhost
- https://pagekite.net/ - Paid DDNS Service
- https://www.noip.com/ - Paid DDNS Service
- https://crontab.guru - Cron job helper
- https://portforward.com/ - Full Guide on Port Forwarding
- https://www.npmjs.com/package/beame-insta-ssl - SSH Tunneling
