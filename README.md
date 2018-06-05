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

The apps in this repository use YAML-formatted file(s) to provide the parameters for gaining access to the Salsa Engage API.

Specifically, each app will read a app-specific YAML file from the 
`params` directory.  That file will provide both the Engage API token
and any runtime parameters that the app needs.

**The `params` directory _should never be checked in_**.  It contains files that
themselves contain credentials.  Checking in the dir will leave your
Engage API token out where bad guys can get to it.

You can see a sample parameter YAML file by viewing the file
`params-template.yaml`.

# License

Please read the LICENSE file.

# Questions? Issues?

Use the [Issues](https://github.com/salsalabs/engage_api_php/issues) page in
GitHub to ask questions and report issues.  
