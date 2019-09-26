# yii2-dev-updater

Yii2 component to simplify composer and migrations updates

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

- `allow_env`: .
- `composerCommand`: .
- `controllerId`: .
- `updaterServices`: .
- `lastUpdateInfoFilename`: .
- `updatingLockFilename`: .
- `sudoUser`: .

## License

This project is open source and available freely under the [MIT license](LICENSE.md).
