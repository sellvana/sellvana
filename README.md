Sellvana
========

Current state: private alpha, not ready for demonstration.

Installation
------------

1. Checkout from bitbucket into web accessible folder e.g. `{webroot}/sellvana`
2. Make sure `dlc/`, `storage/` and `media/` folders are recursively writable for web service
3. Create database and db user for sellvana (make sure db collation is utf8_general_ci)
4. Open the selvana folder in browser
5. Follow the steps in the installation wizard
6. Once installed, you'll be redirected to frontend
7. Open **admin** at `{webroot}/sellvana/admin` (should be already logged in with user info from the wizard)
8. Go to **Modules > Manage Modules**, set modules you'd like to use to `REQUESTED` run level, click **Save**

Issues
-------

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

Documentation
-------------

We have put together a [quick guide](http://sellvana.com/fdoc/fulleron) to get you started. As a community member, we also invite you to collaborate, improve and submit new articles on [https://bitbucket.org/sellvana/sellvanadoc](https://bitbucket.org/sellvana/sellvanadoc).

PHP-FPM support
---------------

A .user.ini is included in the installation, setting parameters for php-fpm
installations. Please review the settings to match your machine and security
policies.

NGINX support
-------------

Nginx will require the
[headers-more](http://wiki.nginx.org/HttpHeadersMoreModule) module to remove
some headers. The following is an example configuration:

    server {
        # Ip's, server_name etc here
        root        /var/www/example.com;

        location / {
            index        index.php;
            if_modified_since    off;
            more_clear_headers    'If-None-Match';
            more_clear_headers    'ETag';
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

        location ~ ^(.+\.php)(.*)$ {
            fastcgi_pass 127.0.0.1:9000;
            fastcgi_split_path_info ^(.+\.php)(.*)$;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            fastcgi_param PATH_INFO $fastcgi_path_info;
            fastcgi_param HTTP_MOD_REWRITE On;
            include fastcgi_params;
        }
    }

Contributing
------------

1. Before starting work on a new contribution, take a moment and search the commits for similar proposals.
2. Fork the Sellvana repository into your account according to [BitBucket's Fork a Repo](https://confluence.atlassian.com/display/BITBUCKET/Fork+a+Repo,+Compare+Code,+and+Create+a+Pull+Request).
3. Make your changes. We also recommend you test your code before contributing.
4. Once ready to commit your changes, create a pull request according to [BitBucket's Create a Pull Request](https://confluence.atlassian.com/display/BITBUCKET/Fork+a+Repo,+Compare+Code,+and+Create+a+Pull+Request).
5. Once received, the Sellvana development team will review your contribution and if approved, will pull your request to the appropriate branch.

Note: You must agree to [Sellvana's Contributor License Agreement](http://sellvana.com/cla) before pulling any requests. You only need to sign the agreement once.
