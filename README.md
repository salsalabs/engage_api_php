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

#License

Please read the LICENSE file.

# Notes

## 15-Jan-2019

I've refactored the sources to us a parameter file provided in the command line.  This lets you
try out different ideas and setting without having to dig around in the source first.  

The most obvious change is that each application needs to have a `--login` parameter.
```
php src/whatever.php --login YAML_Login_File.yaml
```
"YAML_Login_File.yaml" can be any valid YAML file.  All YAML files need to have at least the Engage token and the hostname.  Here's a sample.
```yaml
token: your-incredibly-long-engage-token
host: api.salsalabs.org
```
Other contents may be required by the applications.  Every source file in this repository has a sample YAML file in the opening comments.  Use that as a template, then fill in stuff for your own situation.  Here's an example from `search_fundraising_by_activityid.php`.

```yaml
token:          "your-incredibly-long-token"
identifierType: FUNDRAISE
modifiedFrom:   "2018-07-01T00:00:00.000Z"
modifiedTo:     "2018-07-31T23:59:59.999Z"
activityIds:
    - "3a05282d-c648-4c9f-a880-574211b019d6"
    - "04afe721-ac62-4b2e-aa66-bc6cba7fac71"
    - "17fd2a78-4ff6-4f3a-b2b1-278355716eff"
    - "3a05282d-c648-4c9f-a880-574211b019d6"
    - "48b25138-a021-4eff-89d2-7161d1caed29"
    - "54ecf33b-b7f7-4d83-85d8-eb9ffac97a66"
```
If you need a sample file to copy, then see  `params-template.yaml`.


# Questions? Issues?

Use the [Issues](https://github.com/salsalabs/engage_api_php/issues) page in
GitHub to ask questions and report issues.  
