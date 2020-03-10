#CRAIGSLIST FEED SERVICE
#### Motivation:
Craigslist will block/forbid (403 Forbidden) any kind of automated requests to 
its rss feeds, either you use proxies, VPS, request it from a web-server, or try to do any kind of automated/bulk requests on their feeds, therefore, we can't effectively consume craigslist feeds.
#### Solution:
With this in mind, this script will help you to download a list of craigslist feeds locally on a physical machine, and to service it to the web.
This way you can take advantage of using a Dynamic IP addresses from a Physical Machine to do bulk requests (which are less likely to get blocked) and then service those feeds to your site with an OPML file.
#### How it works:
You add OPML file(s) to */opml/* directory containing all feeds you want, add/edit the configuration credentials and parameters on */config.ini* and that's it.
You'll be able to use the web interface to take a look at source feeds, and download the newly generated OPML containing all craigslist feeds locally stored.
There are two methods, either you use NGROK to service it to the web (port-forwarding), OR, you use the upload method which will send feed files to an online web server via sftp.

Then you can link the generated OPML file on a feed importer like Drupal feeds importer and fire requests at will.

##SETUP GUIDE
This is a Guide on how to set up and run this service.

1. Setup a [WAMP](http://www.wampserver.com/en/) server (Windows) or a web-server on linux & make it run on startup;
2. Put this project on the web directory (Eg.: /var/www/html/craigslist_feed_service OR C:/wamp/www/craigslist_feed_service);
3. Create a config.ini file at root of this project, copy/change the example bellow.
4. Setup a cron job, for example, set it to execute http://localhost/craigslist_feed_service/service.php every minute and the removal at midnight;
    - */1 * * * * /usr/bin/php -q /var/www/html/craigslist_feed_service/service.php >> /var/www/html/craigslist_feed_service/catch_errors 2>&1
    - 0 0 * * * /usr/bin/php -q /var/www/html/craigslist_feed_service/removal_service.php >> /var/www/html/craigslist_feed_service/catch_errors 2>&1
5. Access http://localhost/craigslist_feed_service/ to view the interface;
6. Either use ngrok/pagekit/similar for port-forwarding, or setup ssh credentials on *config.ini* to upload feeds to a live server; 
7. If you use upload method, you need to enable/authorize/download the ssh rsa key with the passphrase, and make sure to create writable folders called *"cl_service"* inside *"public_html"* and the *"feeds"* folder inside the *"cl_service"*
8. Make sure the cron interval you set is equal to the one specified on *'cron_min_interval'* inside your *config.ini* file, otherwise the ETA might not be accurate.

Now you can consume craigslist feeds without getting blocked/forbidden by Craigslist.

### config.ini Example
``` ini
service_name=CL SERVICE
# Method can be 'ngrok' or 'upload'
method=upload
# Web App Path
base_url=/craigslist_feed_service/
# NGROK or localhost No trailing slashes
ngrok_url=http://1a2b3v4.ngrok.io
localhost=http://localhost
# feeds to download locally per cron run of service.php
feeds_per_minute=9
feeds_sleep_seconds=2
cron_min_interval=1
# SFTP Server Credentials
server_base=http://yourwebsite.com/cl_service/
sftp_dir=public_html/cl_service
sftp_server=server.ip.addr
sftp_port=22
sftp_login=username
sftp_key_pass=XxXxXXxxxXxX
sftp_key=id_rsa_cl_service
```
### Observations
- This script executes on an OPML file generated with OPML Editor, please view the file inside /opml/ to understand expected xml structure;
- This also will use the outline title attribute to set feed file names, so it needs to exist;
- This script was created to service a Drupal website using Feeds module https://www.drupal.org/project/feeds/
- Depending on how many feeds you'll fetch from Craigslist, you can tweak config.ini to your needs;

### Useful Links
- http://www.wampserver.com/en/ - Windows PHP Web Server
- http://phpseclib.sourceforge.net/sftp/2.0/examples.html - Secure SSH Communication
- https://crontab.guru - Cron job helper
- https://www.duckdns.org/ - Free Dynamic DNS
- https://ngrok.com/ - Secure Introspect Tunnels to localhost
- https://pagekite.net/ - Paid DDNS Service
- https://www.noip.com/ - Paid DDNS Service
- https://portforward.com/ - Full Guide on Port Forwarding
- https://www.npmjs.com/package/beame-insta-ssl - SSH Tunneling
- https://github.com/erusev/parsedown/ - Rendering README.md markups
