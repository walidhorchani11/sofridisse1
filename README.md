# Safo group e-commerce projects #

* [Learn Markdown](https://bitbucket.org/tutorials/markdowndemo)

## How do I get set up? ###

### Overview

The project runs on a **Apache 2** + **MySQL 5.6** + **PHP 5** + **phpMyAdmin** environment. You can also install MySQL 5.7 on your dev machine.

You'll also need Symfony 2.8 (production servers use other 2.x versions such as 2.5 and 2.7) and Composer.

Note: the standard RedPill and RedPill-5G Wi-fi networks, as of this writing, drop SSL connections erratically, which may adversely affect various downloads, updates etc. (sometimes without any error message at all, which may lead to meaningless "corruption" errors on the files you have just downloaded etc.). It is recommended to use a more reliable Internet connection during the setup - for example, **livebox-hsd** (for password, ask anyone at HSD).

### Alternative 1: Working with a virtual machine

This project uses sereval techniques and packages. Some environment is unified and some content and data must be added to it.

Here is how to do it with VirtualBox.

1. Enter your BIOS and activate virtualization options (F12 on Dell).

1. On your Windows, download the VM image of the roject from the shared repository (ask your project admin).

1. Download and install [VirtualBox](https://www.virtualbox.org/) with default options (if you get a security warning, continue anyway).

1. Import the VM into VirtualBox.

1. Start the VM.

1. Open Ubuntu session as **safo-dev** / **safodev** (remember that password for sudo commands).

1. Enter a terminal, and change directory into your **/Bureau** (or **/Desktop**), and and then into **Additions/** direcory. There should be a file named **VBoxlinuxAdditions.run** .

1. Execute that file with `sudo ./VBoxlinuxAdditions.run`

1. ... When asked password for sudo commands, password is **safodev** . This should install aditional modules to finish env setup.

1. When finished, restart your VM.

1. Do not update/upgrade when asked to.

1. On your VM window, in the VirtualBox top menu, go to **Périphérique** and select **Insérer l'image CD des Additions** and then validate on the VM screen (password is **safodev**).

1. Turn off Ubuntu.

1. Go to the VM parameters (in VirtualBox) and set video memory to 128Mo and add 3D acceleration.

1. Restart the VM, it should go fullscreen and be plenty usable now :)
2. Then, configure your git :
  * `git config --global user.email "you@example.com"`
  * `git config --global user.name "Your Name"`
  * `git config --global core.editor vim` (if you like vim, or another editor. It is set to nano by default).
  * `sudo apt-get install vim` (if you need vim) ; be sure to be on a reliable Wi-Fi network.
  * `git config --global push.default simple` to adopt modern (git 2.0) push strategy (otherwise, you'll get verbose warnings on push).

### Alternative 2: a native Windows installation

#### Apache
1.	Install Apache from one of the binary distributors. Recommended: apachehaus-httpd-2.4.25-x64-vc14-r1.zip from [Apache Haus](http://www.apachehaus.com/cgi-bin/download.plx) 
2.	Unzip it to C:\Alex\bin\Apache
3.	Edit C:\Alex\bin\Apache\Apache24\conf\httpd.conf. Modify the line 38: Define SRVROOT "/Alex/bin/Apache/Apache24" (note that drive letter must be omitted).
4.	Try running httpd.exe from the console. If successful, you should see empty screen with blinking cursor.
5.	If antivirus of Windows firewall complain, allow httpd.exe to access ports 80 and 443.
6.	If httpd fails to run with a message like “could not bind to address 0.0.0.0:80”, you should disable Skype listening on ports 80 and 443 : open the Skype window, then click on Tools menu and select Options. Click on Advanced tab, and go to Connection sub-tab. Uncheck the check box for “Use port 80 and 443”. Click on Save button and then restart Skype (system tray – right click - quit) to make the change effective.
7.	If it is neither antivirus’s nor Skype’s fault, you can find out which program is listening on ports 80 and 443 by executing “netstat -ao -p tcp”.
8.	To test the Apache server running from console, open “localhost” from your browser. You should see Apache Haus test page.
9.	Exit (Ctrl-C) the Apache running from console. To enable it as a service, run the following commands as an administrator:
  1. `httpd.exe -k install`
  1. `httpd.exe -k start`

#### PHP
1.	Download from the [PHP.net](http://windows.php.net/download/) the PHP 5.6.30 (VC11 x64 non-thread-safe version (for fastCGI)).
2.	Unzip it to C:\Alex\bin\php-56
3.	Create an empty folder C:\Alex\bin\php-56\log
4.	Download from [Apache lounge](http://www.apachelounge.com/download/) the fcgid Apache extension mod_fcgid-2.3.9-win64-VC14.zip
5.	Unzip it somewhere (temporary location).
6.	Move mod_fcgid.so to C:\Alex\bin\Apache\Apache24\modules\
7.	Edit C:\Alex\bin\Apache\Apache24\conf\httpd.conf
  1.	Add the following line (for example, at line 127):  LoadModule fcgid_module modules/mod_fcgid.so
  1.	Add “ExecCGI” to the end of the Options line of the <Directory> corresponding to the DocumentRoot (line 262)
  1.	Set AllowOverride to “All” in the <Directory> corresponding to the DocumentRoot (line 269). Unlike for Options line, do not add it to the end of the line, replace “None” by “All”.
  1.	Modify DirectoryIndex (line 282) to index.php
  1.	Copy the following configuration lines (originally, they are from the readme.txt of the fcgid) to the end of httpd.conf. Make sure to modify all paths according to your installation

```
####################################################
# Configuration copied from the readme.txt of the mod_fcgid

FcgidInitialEnv PATH "c:/Alex/bin/php-56;C:/WINDOWS/system32;C:/WINDOWS;C:/WINDOWS/System32/Wbem;"
FcgidInitialEnv SystemRoot "C:/Windows"
FcgidInitialEnv SystemDrive "C:"
FcgidInitialEnv TEMP "C:/WINDOWS/Temp"
FcgidInitialEnv TMP "C:/WINDOWS/Temp"
FcgidInitialEnv windir "C:/WINDOWS"
FcgidIOTimeout 64
FcgidConnectTimeout 16
FcgidMaxRequestsPerProcess 1000 
FcgidMaxProcesses 50 
FcgidMaxRequestLen 8131072
# Location php.ini:
FcgidInitialEnv PHPRC "c:/Alex/bin/php-56"
FcgidInitialEnv PHP_FCGI_MAX_REQUESTS 1000

<Files ~ "\.php$>"
  AddHandler fcgid-script .php
  FcgidWrapper "c:/Alex/bin/php-56/php-cgi.exe" .php
</Files>
```

8. Create an empty file C:\Alex\bin\php-56\php.ini. Add to it the following lines (note the path at the last line):

```
extension_dir=ext

;required by phpMyAdmin
extension=php_mbstring.dll
extension=php_mysqli.dll

;required for https stream wrapper, required by symfony installer
extension=php_openssl.dll

;apparently, required by doctrine
extension=php_pdo_mysql.dll

;recommended by Symfony
extension=php_intl.dll
zend_extension=php_opcache.dll

;required by the Safo project
extension=php_gd2.dll

;required by Symfony
date.timezone=Europe/Paris

;recommended by Symfony - use <?php ?> instead of <? ?>
short_open_tag=off

;recommended by Symfony
realpath_cache_size=10M

;otherwise, sogedial:executeDatabaseSetup will fail
memory_limit = 2048M

log_errors=On
error_log=C:\Alex\bin\php-56\log\php-errors.log
```

9.	Restart the web server as admin: `httpd.exe -k restart`. To be really sure, try `httpd.exe -k stop` followed by `httpd.exe -k start`. The latter version seems more thorough and will also complain if you are not admin.
10.	To test, put the following file index.php into C:\Alex\bin\Apache\Apache24\htdocs\

```
Hello there
<?php phpinfo(); ?>
```

11.	Then, navigate to localhost in your browser. If you still see the Apache Haus page -> you have not restarted the server correctly, it does not use the correct DirectoryIndex (index.php). If you just see “Hello there” and no PHP info, PHP is not working. You’re in trouble.

#### MySQL

1.	Download from [MySQL.com](https://dev.mysql.com/downloads/mysql/) the MySql community server 5.7.17, 64-bit (mysql-5.7.17-winx64.zip).
2.	Unzip it into C:\Alex\bin\mysql-5.7.17-winx64\
3.	Also create a folder C:\Alex\bin\mysql-data\
4.	Go to C:\Alex\bin\mysql-5.7.17-winx64\. Copy my-default.ini to my.ini and add the following lines

```
basedir=C:/Alex/bin/mysql-5.7.17-winx64
datadir=C:/Alex/bin/mysql-data
```

5. Configure PATH (type “environment” in Windows search, click “edit the system environment variables”, then “environment variables”, “system variables”, “Path”, “Edit…”, go to the bottom of the list, click New and enter an additional path for each of the following paths: 
  * C:\Alex\bin\mysql-5.7.17-winx64\bin
  * C:\Alex\bin\php-56
6. To set up the data folder, open the console as admin, then go to bin subfolder and run `mysqld --initialize-insecure`
7. Then, still as admin, to run it as a service, type: `mysqld –install` and then `net start mysql`.
8. Configure the MySQL root password.
  * Type `mysql -u root`.
  * Then, in mysql command line, type the following line to list the users: `SELECT User, Host, HEX(authentication_string) FROM mysql.user;`
  * You should see “root”, “localhost”, empty column, followed by another (mysql system) user. Type `alter user root@localhost identified by 'NEWPASSWD';` where NEWPASSWD is the new root password, of course.
  * You can test it by exiting command line (quit) and trying to re-run it (mysql -u root). The command line interface will now prompt you for the root password.

#### Symfony and Composer
1.	Go to C:\Alex\bin. Download the installer: `curl.exe -LsS https://symfony.com/installer -o ./symfony.phar`
2.	If you don’t have curl, just get it by trying to access the address above from your browser.
3.	Download and run composer-setup.exe from [the composer website](https://getcomposer.org/download/).
4.	Go to a temporary empty folder and type `php C:\Alex\bin\symphony.phar new safo 2.8`
5.	Then, type `php safo/app/check.php`. This will check all Symfony requirements. You should have no errors and no warnings. Then, you can erase the temporary folder with safo subfolder.

#### The project
1.	Edit C:\alex\bin\Apache\Apache24\conf\httpd.conf and set DocumentRoot and the Directory entry on the following line (lines 248-249) to "/Alex/git/catalogue-sofridis/web" (note that drive letter must be omitted).
2.	Restart the web server (as admin: httpd.exe -k stop ; then httpd.exe -k start).
3.	Clone the project (see “Common steps” below).
4.	Install phpMyAdmin
  1.	Download and install from [the official site](https://www.phpmyadmin.net/downloads/) phpMyAdmin-4.6.6-all-languages.zip
  1.	Unzip it to the web folder of your git clone of the Safo project, for instance C:\Alex\git\catalogue-sofridis\web\phpmyadmin\
  1.	You should use “phpmyadmin” folder name so that it is correctly ignored by git.
  1.	Put the file .htaccess into C:\Alex\git\catalogue-sofridis\web\phpmyadmin\. This file should contain a single line “DirectoryIndex index.php”
  1.	Try to access “localhost/phpmyadmin” to see if the MySql connection works correctly. You can use MySQL root credentials you have just set.
5.	Continue common steps (below) from “Prepare the server”, step 4 (but using the password for MySQL root that you have just configured).
6.	As a special Windows bonus, you won’t need to execute chmod and setfacl commands for app/cache and app/logs :-)
7.	Enjoy.



### Common steps (for both alternatives)

#### Clone the project

1. Open a terminal.

1. Change directory to where you wish to put the project files. The VM image already has this kind of directory for that use: **/home/safo-dev/www/** .

1. Type `ssh-keygen` and validate with default values (also works on Windows if you have git + Linux command line tools installation).

1. When provided with the SSH key, copy the string corresponding to the public key ([Ctrl]+[C]). On Linux systems, it can be located in ~/.ssh/id_rsa.pub. On Windows, in C:\Users\USERNAME\\.ssh\id_rsa.pub.

1. Open a browser (inside the VM, if any, to be able to paste) and go to your GitLab account. The go to **Settings** > **SSH keys** and paste all your key inside the **Key** input field. You can provide a title. Then click on **Add key** .

1. Go back to your term and type `git clone git@gitlab.com:safodev/catalogue-sofridis.git`

1. ... This will provide you a copy of the project under the directory **catalogue-sofridis/** .

#### Prepare the server

1. (VM only) Edit **/etc/apache2/sites-available/dev.conf** .

1. (VM only) Change the 2 references of URL with the **web** URL of the cloned project (this could be something like **/home/safo-dev/www/catalogue-sofridis/web**).

1. (VM only) From your terminal, (re)start your Apache server with `sudo service apache2 restart`

1. Now open your browser and go to **localhost/phpmyadmin** .

1. Log in with **root** / **rootroot** (or another password you have just configured in Windows).

1. Create a new database named **catalogue** with **utf8_general_ci** encoding.

1. Go back to your terminal and execute `composer install`

1. ... You will be asked a few questions for configuration:

 1. **database_driver (pdo_mysql):** : leave default (keep blank and hit [Enter]).
 1. **database_host (127.0.0.1):** : leave default or type `localhost` (recommended).
 1. **database_port (null):** : leave default.
 1. **database_name:** : type `catalogue`
 1. **database_user:** : type `root`
 1. **database_password:** : type `rootroot` (or another password)
 1. Leave all other values by default (blank or otherwise).

1. Go to your terminal and type `php app/console` to get a list of custom commands (this means your install is all good so far).

1. Type `php app/console assets:install`

1. Type `php app/console assetic:dump`

1. Type `sudo rm -rf app/cache/*`

1. Type `sudo rm -rf app/logs/*`

1. (Linux only) Type `sudo chmod -R 0777 app/cache/ app/logs/*`

1. Go back to your browser: now you should be able the login screen at **http://dev.sogedial.fr** .

#### Prepare the data to work with

1. In your VM browser, open a new tab and connect to your HSD Google account.

1. Go to your **GoogleDrive** > **Shared with me** > **RedPill** > **02-Data** > **Sofridis** .

1. Download, to your VM storage, the files **entreprise.sql**, **etatcommande.sql**, **region.sql**, and **import.zip** (**import-utf8.zip** on Windows).

1. Unzip **import.zip** (**import-utf8.zip** on Windows) and move the **import** folder into **www/catalogue-sofridis/web/uploads/** .

1. Type `sudo rm -rf app/logs/*`

1. Type `php app/console` to check if everything is still ok (list displayed = everything okay).

1. (Linux only) Give right by typing `sudo chmod -R 0777 app/logs/ app/cache/`

1. type `php app/console doctrine:schema:update --force`

1. Go back to your PhpMyAdmin and import SQL files in that order: (you will eventually get errors because tables already exist, but data will be imported)

  1. **region.sql**
  1. **entreprise.sql**
  1. **etatcommande.sql**

1. Type `php app/console sogedial:executeDatabaseSetup` to retrieve all the data.

1. ... Since the previous step will take several minutes, you could go and get some coffee ;).

#### Add some users

1. Go back to your term and create the first admin user by typing `php app/console fos:user:create`

  1. name: `admin`
  1. e-mail address: your e-mail address (might be useful)
  1. password: something you could easily remember.

1. Type `php app/console fos:user:promote`

  1. name: `admin`
  1. role: `ROLE_ADMIN`

1. (Linux only) Definitively update rights by executing the 3 following commands:

  1. `sudo chmod -R 777 app/cache`
  1. `sudo chmod -R 777 app/logs`
  1. `sudo setfacl -dR -m u::rwX app/cache app/logs`

1. Log in to the web interface with the **admin** user you just created.

1. In the left menu of the dashboard, click on **Mes clients**.

1. Edit the first user of the list.

1. Choose the login for the user. His client code (eg: `C14411`) is a good idea. Memorize it.

1. Enter a password (and memorize it :-).

1. Save settings.

1. Log out.

1. Now you can log in with the new user you just created (activated).

#### Edit files

1. Files to work on are accessible in **src/Sogedial/IntegrationBundle/Resources/views/...**

1. On every JS or CSS change, reload the assets by typing `php app/console assets:install` and `php app/console assetic:dump` to see the changes on the website.
2. On every database schema change, run `php app/console doctrine:schema:update --force`.
3. Twig changes are taken into account immediately (need to refresh Browser window, though).

#### (How to run tests)
#### (Deployment instructions)

## Contribution guidelines ###

* Coding style : see [Symfony guidelines](http://symfony.com/doc/2.7/contributing/code/standards.html)
  * Code tab size = 4, tabulation with spaces.
* Writing tests
* Code review

## Who do I talk to? ###

* Repo owner or admin
* Other community or team contact