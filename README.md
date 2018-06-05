# engage_api_php
Examples of accessing Engage via the API with PHP. 

This repository contains demonstrations of using the Salsa Engage API with PHP.

These demos were *not* written by a PHP developer.  Please feel free to make a pull request if you'd like to improve them.

# Dependencies

These apps depend upon these tools and libraries.

* [Composer](https://getcomposer.org/)
* [GuzzleHTTP](http://docs.guzzlephp.org/en/stable/)
* [Symfony YAML](http://symfony.com/doc/current/components/yaml.html)
* [Salsa Engage API Doc](https://help.salsalabs.com/hc/en-us/articles/115000341773)

# Installation (brief)

Use these steps to install and equip this repository.

1. [Clone this repository.](https://github.com/salsalabs/Engage_api_php)
1. [Install composer.](https://getcomposer.org/)
1. Install dependencies
``` bash
composer require guzzlehttp/http
composer require symfony/yaml
composer upgrade
```
# Logging in to the API

The apps in this repository use `credentials.yaml` to provide the parameters for gaining access to the Salsa Engage API.

* API URL
* email
* password

Here is a sample credentials.yaml that you can use.
```
api_host: https://wfc2.wiredforchange.com
email: aleonard@salsalabs.com
password: a-really-long-and-complicated-password
whatever: 123456
```
# Usage

Make sure that the contents of `credentials.yaml` are correct, then type

`php any_php_filename.php`

# Licencse

Please read the LICENSE file.

# Questions? Issues?

Use the [Issues](https://github.com/salsalabs/engage_api_php/issues) page in
GitHub to ask questions and report issues.  
