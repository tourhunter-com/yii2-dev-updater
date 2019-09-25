# yii2-dev-updater

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

## Code of Conduct

This project and everyone participating in it is governed by the [Code of Conduct](CODE_OF_CONDUCT.md).

## Contributing

Please read through our [Contributing Guidelines](CONTRIBUTING.md).

## License

This project is open source and available freely under the [MIT license](LICENSE.md).
