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

# License

Please read the LICENSE file.

# Authentication

All of the demo apps use a common utility class called `DemoUtils`. DemoUtils
provides a standard set of tools for retrieving API tokens and parameters.

DemoUtils also provides some basic services to make the demos easier to read.
Apps do not have to manage the interface to GuzzleHTTP, for example.  They can
simply start reading.

## Token storage

Tokens and parameters are stored in a YAML file.  DemoUtils parses that YAML
file and stores the tokens in class accessors.  DemoUtil also stores the full
contents of the YAML file as an object. 

Here's a YAML file example.

```yaml
intToken:   "9fna8wir-hmmr-9oae-78wd92gbduao"
devToken:   "rufyjn5h-ucet-kd5w-tdyyhgzjjh7j"
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

* The `intToken` field is required when you are making Engage Integration API calls.
* The `devToken` field is required when you are making Engage Web Developer API calls.
* The remainder of the fields are stored in a hash the Environment field in DemoUtils.

If you need a sample file to copy, then see  `params-template.yaml`.

# Usage:

The examples are stored in a directory structure quite a lot line the one
used in the [Integration API](https://api.salsalabs.org/help/integration) documentation.
For example, you can find calls that affect activities in `src/activities`, a way to 
find segment members in `src/segments`, and info about email blasts in `src/email_blast`.



```bash
php src/actities/fundraising/search_donations_by_date.php

You must provide a parameter file with --login!
```

Pretty straightforward, huh?  You provide a parameter/configuration file, and the
app goes to work.

```bash
php src/activities/fundraising/search_donations_by_date.php --login your_own.yaml

Activity ID                          Transaction ID                       Transaction Date         Type         Amount
    7a5e2234-f8a9-4aa5-8ded-8cba4758c38e 523cf742-2ab9-42b7-987a-b38795844343 2021-01-01T14:09:16.600Z CHARGE        25.00
    6b2bf336-c8d9-4268-b02c-381f63a531b1 5db252c8-bd70-43df-bac3-5b5f32fc16c2 2021-01-01T21:29:43.241Z CHARGE        25.95
    e8af1ebf-0b7a-40b2-8830-7abc0b5806c3 b8d040b2-d9a1-470d-a622-94d0ac514fdb 2021-01-01T21:59:16.409Z CHARGE        20.00
    c16bb60f-f678-4f93-a04e-90047f6e05d3 3b473060-1f86-45c5-9946-f1d8e588b918 2021-01-02T17:19:38.307Z CHARGE        25.00
    2e24020b-9190-4e2d-ac46-6e4e284fb818 32a51391-a20e-43ea-9614-4315a79ed275 2021-01-02T19:33:58.519Z CHARGE       100.00
    fcd6e773-2146-4375-8a79-84bb53858130 60f54b7c-0bbf-48e0-8c07-aff4fd519497 2021-01-02T20:03:11.263Z CHARGE        30.00
    2fdf34cd-82b2-4aee-a43b-74d6f474b5cb 8c8e6a74-49af-48dc-8403-787ca69c6362 2021-01-02T22:36:48.027Z CHARGE         5.00
    af0834dc-7a60-4c57-874b-af2623b8710c 69e5fd53-9133-40a7-b716-b065e578aeab 2021-01-03T00:12:13.083Z CHARGE        25.95
    38b79ab9-c7e5-447e-b643-2e216e6fbce7 9a312293-fd53-4e4b-9c61-caa7629df2e3 2021-01-03T13:31:03.555Z CHARGE        25.00
    e81d5f67-1a46-4f92-a257-769c89d10991 74614fd6-6273-4adb-b3b1-5ef95d06e5c0 2021-01-03T13:43:30.882Z CHARGE       172.00
    f63e845a-0c1d-47a4-9ee6-2e26b3daaa42 6d992faa-3a41-4d8c-a286-6a63805bedc6 2021-01-03T23:21:55.020Z CHARGE       103.18
    d61dbb48-c752-4dc3-9ea6-7286d83d3c65 59f15305-285a-4dc3-bfce-de602ba71520 2021-01-04T00:46:09.139Z CHARGE        50.00
    8d53897f-8042-41b0-b478-99b05a2e83a9 41e45a10-1e7d-4606-8b43-25282c84af62 2021-01-04T17:42:53.792Z CHARGE        25.00
    d693679b-e6fc-46b5-a19e-12c2bb107a62 7b49fe4a-fcfc-439e-94ab-dfa68cfa2304 2021-01-04T18:40:42.158Z CHARGE       100.00
    264d722c-b77d-4dae-9edc-fa4090d98a8e adfa1a64-77ba-467d-91db-67c6b51b90be 2020-12-31T20:37:29.001Z CHARGE        51.69
    20c443b9-8f74-4fa2-9424-d88f359b4d80 d16a2479-0d08-4af3-a8a2-b19d3bc62f20 2021-01-02T21:15:09.468Z CHARGE        50.00
    3b928dfd-b901-4f22-942d-d0ad5b2951e3 8e4048e1-d3c8-4953-b7dc-409be37f8f22 2020-12-31T18:13:12.543Z CHARGE       103.18
    b6b9c30a-e757-4920-844e-08179b6df8c5 f978db97-6257-4e92-8dcc-36a9151ca6fe 2020-12-30T21:20:17.508Z CHARGE        50.00
    748f6c9a-bd28-4163-98ee-37f15e046c07 5a32400e-7b63-40ed-8d52-728138fea73b 2021-01-05T05:59:40.212Z CHARGE       103.18
    23d25424-4a9b-4bbe-bb32-8e93a62e2729 cdb6620c-c606-4831-98a3-5b7e76207cef 2021-01-05T14:26:43.133Z CHARGE       360.00
                                                                                                       Total       1450.13

```

# Questions? Issues?

Please direct any questions or comments to [Salsa Support](mailto:support@salsalabs.com).
We'll be glad to help.
