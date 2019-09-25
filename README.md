yii2-dev-updater
====================================

Yii2 component that provide more easier way for composer and migrations updating

## Installation

To add extension to your dependencies, execute:
```
composer require tourhunter-com/yii2-dev-updater
```

## Usage

Once the extension is installed, add following code to your application configuration :
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

- `allow_env`: .
- `composerCommand`: .
- `controllerId`: .
- `updaterServices`: .
- `lastUpdateInfoFilename`: .
- `updatingLockFilename`: .
- `sudoUser`: .

License
-------

[MIT](LICENSE)