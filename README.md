JSAMI
=====

A PAMI port for Javascript. Including a simple PHP WebSocket server to patch the requests through to Asterisk AMI.

JSAMI is a Javascript port to [PAMI](https://github.com/marcelog/PAMI) project. Since at the moment Asterisk does not
support AMI over WebSocket, I've also included a modified version of [php-websocket](https://github.com/lemmingzshadow/php-websocket)
project to patch through WebSocket requests between JSAMI and Asterisk's AMI which is a temporary solution. I hope someday
Asterisk would support AMI over WebSocket.

License Issues:
---------------

	This project make use of three other projects, each of which come with their own licenses (even incompatible with project's license in some cases).

How to use:
-----------

	-1. You will need an Apache Web Server installed with the rewrite module enabled (due to Pomegranate framework requirments). Also phpcli as for the WebSocket server.
	
	0. Download and copy [Zend Framework 1.12](http://framework.zend.com/) to your hard disk. You also need to set the Zend Framework's path in PHP's include_path.
	
	1. Create a virtual host in Apache. This is usually done by adding the following block to your httpd.conf file:
		NameVirtualHost *:80
		<VirtualHost *:80>
			DocumentRoot /var/www/virtual-hosts/jsami/webroot
			ServerName jsami.local
			<Directory /var/www/virtual-hosts/jsami/webroot>
				AllowOverride All
			</Directory>
		</VirtualHost>
	Note: An Apache restart is in order after this step. But you need to wait for the webroot folder to be actually created before you restart the Apache.

	2. Introduce the domain jsami.local to your /etc/hosts file by adding the following line to it:
		127.0.0.1       jsami.local

	3. Copy the project's contents into /var/www/virtual-hosts/jsami (including two folders of webroot and offweb).

	4. Amend paths. If you didn't follow the paths proposed in this readfile, you need to modify the folllowing files and set your own paths:
		./webroot/.htaccess
		./offweb/config/config.ini

	5. Amend Asterisk's connection info. Open the folllowing file and set the Asterisk's connection information:
		./offweb/cli/phpwebsocket/lib/WebSocket/Application/AMIApplication.php

	6. Run the WebSocket server:
		php -q /var/www/virtual-hosts/jsami/offweb/cli/phpwebsocket/server.php
	Note: Before you run the WebSocket server make sure that your Asterisk is up and running.

	7. Make sure that Apache has full access to the offweb folder:
	sudo chmod 777 -R /var/www/virtual-hosts/jsami/offweb

	8. Open the following URL in a browser:
		http://jsami.local

Things to be done:
------------------

	1. It would be nice if someone would spend the time to add the WebSocket support for AMI in Asterisk.

Libraries used:
---------------

	1. [Pomegranate framework](http://www.sourceforge.net/p/pome-framework) as the base Javascript library.

	2. [jQuery](http://www.jquery.com).

	3. [php-websocket](https://github.com/lemmingzshadow/php-websocket).

	4. [PAMI](https://github.com/marcelog/PAMI).