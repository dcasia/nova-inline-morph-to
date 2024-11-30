# Nova Inline MorphTo Field

[![Latest Version on Packagist](https://img.shields.io/packagist/v/digital-creative/nova-inline-morph-to.svg)](https://packagist.org/packages/digital-creative/nova-inline-morph-to)
[![Total Downloads](https://img.shields.io/packagist/dt/digital-creative/nova-inline-morph-to.svg)](https://packagist.org/packages/digital-creative/nova-inline-morph-to)
[![License](https://img.shields.io/packagist/l/digital-creative/nova-inline-morph-to.svg)](https://raw.githubusercontent.com/dcasia/nova-inline-morph-to/master/LICENSE)

![Laravel Nova Inline MorphTo Field in action](https://raw.githubusercontent.com/dcasia/nova-inline-morph-to/master/demo.gif)

### Install

```
composer require digital-creative/nova-inline-morph-to
```

### Usage

The signature is the same as the default `MorphTo` field that ships with Nova.

```php
use DigitalCreative\InlineMorphTo\InlineMorphTo;

class Article extends Resource
{
    public function fields(Request $request)
    {
        return [
            ...
            InlineMorphTo::make('Template')->types([
                \App\Nova\Video::class,
                \App\Nova\Image::class,
                \App\Nova\Text::class,
                \App\Nova\Gallery::class,
            ]),
            ...
        ];

    }
}
```

## License

The MIT License (MIT). Please see [License File](https://raw.githubusercontent.com/dcasia/nova-inline-morph-to/master/LICENSE) for more information.
