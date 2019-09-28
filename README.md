# yii2-dev-updater

Yii2 component to simplify composer and migrations updates

It helps to track the presence of changes in migrations and composer packages after new commits in git, and allows to trigger updates with just one click in browser. 

## Code of Conduct

This project and everyone participating in it is governed by the [Code of Conduct](CODE_OF_CONDUCT.md).

## Contributing

Please read through our [Contributing Guidelines](CONTRIBUTING.md).

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require tourhunter-com/yii2-dev-updater
```

or add

```
"tourhunter-com/yii2-dev-updater": "*"
```

to the require section of your `composer.json` file.

## Usage

Once the extension is installed, add following code to your application configuration:

```php
return [
    'bootstrap' => ['devUpdater'],
    //.....
    'components' => [
        //.....
        'devUpdater' => [
            'class' => 'tourhunter\devUpdater\DevUpdaterComponent',        
        ],
    ]
]
```

Following properties are available for customizing the updater component behavior.

#### allow_env

Defines the list of environments the component will activate in.

```php
    'allow_env' => [ 'dev' ],
```

#### composerCommand

It is possible to set up a console command for the Composer launch, if it's different from the default one.

```php
    'composerCommand' => 'composer',
```

#### controllerId

This parameter influences the controller name in the component page route.

```php
    'controllerId' => 'dev-updater',
```

#### updaterServices

Defines a set of the active component update services, by default it looks this way:
```php
    'updaterServices' => [
         'tourhunter\devUpdater\services\MigrationUpdaterService',
         'tourhunter\devUpdater\services\ComposerUpdaterService',
     ],
```

It is possible to disable composer or migrations updates by removing the related service from the list.
Also it is possible to add a custom solution of the update logic, but that service must extend the class `tourhunter\devUpdater\UpdaterService`

#### lastUpdateInfoFilename

A file path to store the important information about the status of previous updates or errors.
```php
    'lastUpdateInfoFilename' => '@runtime/devUpdaterInfo.json',
```

#### updatingLockFilename

A lock file path that contains info of the update process execution. 

```php
    'updatingLockFilename' => '@runtime/devUpdater.lock',
```

#### sudoUser

A username to execute update console commands.

```php
    'sudoUser' => false,
```


In case of web server working under default users like apache or www-data it may cause problems during some commands' execution.
For example, composer needs a default user folder to cache data.
To avoid this problem, the component supports sudo commands execution.
But before using that you must configure your server accordingly.

First of all you need to know whether you have a `mpm_itk` module on - it will block sudo usage.
You need to add this into apache config:

```
<IfModule mpm_itk_module>
    # Permit using "sudo"                             
    LimitUIDRange 0 65534
    LimitGIDRange 0 65534
</IfModule>
```

The next step is to allow web server user to execute all necessary commands under different users.
The settings in `/etc/sudoers`:

```
www-data ALL=(ALL) NOPASSWD: /bin/composer,/var/www/myYii2Project/yii
```

An essential thing is that you must show the right ways to the files used by the component.
By default it is a console utility of Yii2 framework.

Also make sure to comment the following code in `/etc/sudoers` if it is there:

```
#Defaults    requiretty
```

## License

This project is open source and available freely under the [MIT license](LICENSE.md).
