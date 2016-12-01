# Sellvana

[![Join the chat at https://gitter.im/sellvana/sellvana](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/sellvana/sellvana?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

Current state: public beta.

## Installation

1. Checkout from bitbucket into web accessible folder e.g. `{webroot}/sellvana`:

        git clone git@bitbucket.org:sellvana/core.git

    * If you did not setup public key authentication with bitbucket, use HTTPS link:

        git clone https://your_bitbucket_user@bitbucket.org/sellvana/core.git

2. Make sure `dlc/`, `storage/` and `media/` folders are recursively writable for web service
3. Create database and db user for sellvana (make sure db collation is utf8_general_ci)
4. Open the selvana folder in browser
5. Follow the steps in the installation wizard
6. Once installed, you'll be redirected to frontend
7. Open **admin** at `{webroot}/sellvana/admin` (should be already logged in with user info from the wizard)
8. Go to **Modules > Manage Modules**, set modules you'd like to use to `REQUESTED` run level, click **Save**

## Isolating Sellvana in one subfolder under website root

Sometimes you'd want to keep Sellvana within one subfolder in your web root, instead of mixing Sellvana's files and folders with other applications. Also, this helps if you use git for updates.

1. Follow the installation instructions above, and you can install in a subfolder with any name you choose. For the
current instructions and to avoid confusion we'll use "SELLVANA" as subfolder name.

2. Create `index.php` in website root with the following contents:


    <?php
    require_once __DIR__ . '/SELLVANA/core/FCom/Core/Main.php';
    BConfig::i()->set('fs/root_dir', 'SELLVANA')->set('web/base_src', 'SELLVANA');
    FCom_Core_Main::i()->run('FCom_Frontend');

3. Copy `.htaccess` file from sellvana root into your website root.

4. If you will be using API, edit `.htaccess` to add the following into `<IfModule mod_rewrite.c>` section:


    RewriteRule ^api(.*)$ SELLVANA/api/index.php/$1 [L]

    # BEFORE THIS LINE:    RewriteRule ^(.*)$ index.php/$1 [L]

5. Now you should be able to access Sellvana by browsing your website root. To access admin section, you'll need to add SELLVANA to your URL: `https://mysite.com/SELLVANA/admin`

## Moving `storage` folder from web accessible folder

Sellvana uses randomly generated folder for sensitive cache, log, etc. files to avoid direct access from web, however in some server misconfiguration cases, this might not be enough.
To move `storage` folder from web accessible folder, you will need to perform the following steps:

1. Create a folder where you'd like to keep transient data (let's say `/home/user/sellvana/storage`) and make it writable for web service user.

2. If you already have Sellvana installed, move `{sellvana}/storage` contents to newly created folder.

3. Create `index.global.php` in the root folder where Sellvana's `index.php` lives, and add this:
 
 
    <?php
    
    BConfig::i()->set('fs/storage_dir', '/home/user/sellvana/storage');

## Test Data

If you'd like to generate test catalog data, please open this URL (replace 127.0.0.1/sellvana with your location):

        http://127.0.0.1/sellvana/admin/index.php/catalogindex/test

This is a simple test script and should be ran only once.


## Shell commands

Sellvana allows to be installed and control some aspects via command line interface.
To see available options, please run:

`$ php shell.php`


## Experimental and unfinished modules
 
This repo includes modules that are not fit for production use exist in this repo only for development and feedback purposes. 
These modules live in `dev` folder 


## Fastest Performance Configuration

To test fastest configuration timing and best memory consumption, set the following configuration:

1. Go to Admin > System > Settings
2. Areas > Fulleron Core

    * DB Settings > Enable Logging = No
    * DB Settings > Implicit Migration = No
    * Optimizations > **All settings** = Enable Always

3. Areas > Fulleron Admin > Area Settings > IP: mode >  Default = DEVELOPMENT
4. Areas > Fulleron Frontend > Area Settings > IP: mode >  Default = DEVELOPMENT

After setting this configuration and the first page load for cache warm up, click on [DBG] link in the top right corner.

The first line will have numbers like: `DELTA: 0.091005086898804, PEAK: 4456448, EXIT: 4456448`

The first number is total seconds to load the page, PEAK is maximum memory used, and EXIT what was the memory usage at
the end of page request.

Please note, that having `xdebug` or other debugging/profiling php extension enabled significantly reduces performance.

Since the project is still in rapid development, restore the configuration to allow automatic code and data updates:

1. Areas > Fulleron Core

    * DB Settings > Implicit Migration = Yes
    * Optimizations > **All settings** = Enable in staging or production modes


## Issues

To report a bug, go to [Sellvana's Bug Tracker](https://bitbucket.org/sellvana/core/issues).

1. Ensure the bug is not already reported by searching Sellvana's Issues Tracker.
2. Click the Issues tab in navigation bar.
3. In the Title field, enter a short summary title that clarifies the issue encountered.
4. In the Description field, describe the issue encountered in detail. To expedite the handling of an issue, we recommend you use the following template when reporting an issue.

    * Describe briefly what the intention was of the action that was being performed.
    * **Environment:** Enter PHP and MySQL versions, server details (i.e. local computer server, dedicated, cloud), browser type and version, and anything else that might be helpful to troubleshoot the bug.
    * **Steps to Reproduce:** Add steps taken to achieve the task described in bulleted form.
    * **Expected Result:** List the result you were expecting while performing the steps above (i.e. I should have been directed to the registration form page)
    * **Actual Result:** Result experienced taking the steps above. (i.e. Clicking register button directs me to the checkout page)

5. Assign to Sellvana team.
6. In the Kind dropdown list, select the type of issue being shared.
7. In Priority dropdown list, assign an appropriate priority option.
8. Attach applicable screenshots (if any).
5. Click *Create issue* button to submit your issue.

**NOTE**: For general questions, peer support or working with Sellvana should be shared on the [community board](http://sellvana.com/community/).

## Documentation

We have put together a [quick guide](http://sellvana.com/fdoc/fulleron) to get you started. As a community member, we also invite you to collaborate, improve and submit new articles on [https://bitbucket.org/sellvana/sellvanadoc](https://bitbucket.org/sellvana/sellvanadoc).

## PHP-FPM support

A .user.ini is included in the installation, setting parameters for php-fpm
installations. Please review the settings to match your machine and security
policies.

## NGINX support

Nginx will require the
[headers-more](http://wiki.nginx.org/HttpHeadersMoreModule) module to remove
some headers. The following is an example configuration:

    server {
        # Ip's, server_name etc here
        root        /var/www/example.com;

        location / {
            index        index.php;
            if_modified_since    off;
            # more_clear_headers    'If-None-Match';
            # more_clear_headers    'ETag';
            etag                off;
            try_files    $uri $uri/ @sellvana;
        }

        location /.git { deny all; }
        location ~ \.(yml|twig)$ { deny all; }
        location ~ \.(jpg|jpeg|png|webm|svg)$ {
            expires    30d;
        }

        location @sellvana {
            rewrite ^(.*)$ /index.php$1;
        }
        
        location ~ \.(yml|twig)$ {
            return 403;
        }
        
        location ~ (^|/)\. {
            return 403;
        }
        
        location /storage {
            return 403;
        }

        location ~ ^(.+\.php)(.*)$ {
            fastcgi_pass 127.0.0.1:9000;
            ## or use unix socket, depepnding on your FPM config:
            # fastcgi_pass unix:/run/php/php7.0-fpm.sock;
            
            ## you must ensure your php.ini or php-fpm pool configuration has
            ## not disabled security.limit_extensions for PATH_INFO to not
            ## have a security issue
            
            fastcgi_split_path_info ^(.+\.php)(.*)$;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            fastcgi_param PATH_INFO $fastcgi_path_info;
            fastcgi_param SELLVANA_MOD_REWRITE On;
            include fastcgi_params;
        }
    }

## Contributing

1. Before starting work on a new contribution, take a moment and search the commits for similar proposals.
2. Fork the Sellvana repository into your account according to [BitBucket's Fork a Repo](https://confluence.atlassian.com/display/BITBUCKET/Fork+a+Repo,+Compare+Code,+and+Create+a+Pull+Request).
3. Make your changes. We also recommend you test your code before contributing.
4. Once ready to commit your changes, create a pull request according to [BitBucket's Create a Pull Request](https://confluence.atlassian.com/display/BITBUCKET/Fork+a+Repo,+Compare+Code,+and+Create+a+Pull+Request).
5. Once received, the Sellvana development team will review your contribution and if approved, will pull your request to the appropriate branch.

Note: You must agree to [Sellvana's Contributor License Agreement](http://sellvana.com/cla) before pulling any requests. You only need to sign the agreement once.

## Built-in SCSS and LESS processors

Sellvana has built-in libraries to compile SCSS and LESS files into CSS, so no external server setup and watchers are necessary.

To declare SCSS or LESS file, add the following lines to your `layout.yml` file:
 
    
    - [ view: head, do: [[ scss, "@My_Module/Frontend/scss/mystyles.scss" ]] }
    - [ view: head, do: [[ less, "@My_Module/Frontend/less/mystyles.less" ]] }
    
The resulting built CSS files will be added `.build.css` to their name and saved within the same folder as the original files during page rendering (in `DEBUG` and `DEVELOPMENT` modes), so make sure they are writable for web service user in your dev environment. 

## Setting up crontab

Use `cron.php` to run cron tasks from your shell, crontab setup or web.

## Versioning

Since all the functionality is contained in modules, the global Sellvana version is only an initial download package version.
All the issue reports will require full list of currently installed module versions. If using built-in reporting, this will be done automatically.

The global Sellvana package versions are: `[X].[Y].[Z]` (example: `1.0.5`), where:

  * `[X]` - Sellvana generation version, before stable is `0`, the first version is `1`, etc.
  * `[Y]` - Main release iteration version within the generation
  * `[Z]` - Bug and Security fixes releases version
  * All parts start from `0`.

The module versions are: `[X].[Y].[Z].[P]` (example: `1.0.12.1`), where:

  * `[X]` - Sellvana generation version, before stable is `0`, the first version is `1`, etc.
  * `[Y]` - Feature set version. All subversions of this version should have the same feature set.
  * `[Z]` - DB level version. Each time there's data structure change, this part will be bumped up.
  * `[P]` - Patch version. If fixes are required without data structure change, this part will be bumped up.
  * All parts start from `0`.
