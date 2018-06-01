# engage_api_php
Examples of accessing Engage via the API with PHP. 

# Background
These are example programs that implement calls from the [Engage Integration](https://help.salsalabs.com/hc/en-us/sections/205407008-API-Engage-Integration) 
and [Engage Web Developer](https://help.salsalabs.com/hc/en-us/sections/360000258473-API-Web-Develope)
APIs.

The samples are being built up over time as the API evolves and clients have questions.

# Dependencies
These expamples depend upon these PHP packages.

* "[guzzlehttp/guzzle](http://docs.guzzlephp.org/en/stable/overview.html)":  Excellent web I/O library
* "[symfony/yaml](https://symfony.com/doc/current/components/yaml.html)": YAML file I/O for credentials

# Install and Build
1. Clone this repository using git.
1. Install [Composer](https://getcomposer.org/)
1. Type this command into the console
```bash
composer
```
You should see some feed back like this:

```bash
Loading composer repositories with package information
Updating dependencies (including require-dev)
Package operations: 6 installs, 0 updates, 0 removals
  - Installing guzzlehttp/promises (v1.3.1): Loading from cache
  - Installing psr/http-message (1.0.1): Loading from cache
  - Installing guzzlehttp/psr7 (1.4.2): Loading from cache
  - Installing guzzlehttp/guzzle (6.3.3): Loading from cache
  - Installing symfony/polyfill-ctype (v1.8.0): Downloading (100%)
  - Installing symfony/yaml (v4.1.0): Downloading (100%)
guzzlehttp/guzzle suggests installing psr/log (Required for using the Log middleware)
symfony/yaml suggests installing symfony/console (For validating YAML files using the lint command)
Writing lock file
Generating autoload files
```

# Runtime configuration

Apps that use the Engage APIs need an [Engage API Token](https://help.salsalabs.com/hc/en-us/articles/224470007-Getting-Started#acquiring-a-token).  They may also need optional parameters.

These apps most generally use a YAML file to hold the API token and any runtime parmaeters. View the `params-template.yaml` file for an example of providing the API token and runtime parameters.

# License

Read the `LICENSE` file.

# Questions? Issues?

Use the [Issues](https://github.com/salsalabs/engage_api_php/issues) link.  Salsalabs does not provide 
support for these examples.  Contacting Salsa Support will be a waste of your time.






