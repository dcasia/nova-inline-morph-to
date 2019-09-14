# Nova Inline MorphTo Field

[![Latest Version on Packagist](https://img.shields.io/packagist/v/digital-creative/nova-inline-morph-to.svg)](https://packagist.org/packages/digital-creative/nova-inline-morph-to)
[![Total Downloads](https://img.shields.io/packagist/dt/digital-creative/nova-inline-morph-to.svg)](https://packagist.org/packages/digital-creative/nova-inline-morph-to)
[![License](https://img.shields.io/packagist/l/digital-creative/nova-inline-morph-to.svg)](https://github.com/digital-creative/nova-inline-morph-to/blob/master/LICENSE)

![Laravel Nova Inline MorphTo Field in action](https://github.com/dcasia/nova-inline-morph-to/raw/master/demo.gif)

### Install

```
composer require digital-creative/nova-inline-morph-to
```

### Usage

The signature is the same as the default `MorphTo` field that ships with Nova.

```php
class Article extends Resource
{
    use HasInlineMorphTo;

    public function fields(Request $request)
    {
        return [
            ...
            InlineMorphTo::make('Template')
                         ->types([
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

**_Note:_** You will need to import the `HasInlineMorphTo` trait for this field to display correctly within resource detail views. 

## License

The MIT License (MIT). Please see [License File](https://github.com/dcasia/nova-inline-morph-to/raw/master/LICENSE) for more information.
