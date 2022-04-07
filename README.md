# Filament Advanced Filter

Advanced filter component for filament admin

## Installation

Install the package via composer (requires filament >= 2.10.40)
```bash
composer require webbingbrasil/filament-advancedfilter
```

## Available Filters

### BooleanFilter

Filter records by boolean column:

```php
use Webbingbrasil\FilamentAdvancedFilter\Filters\BooleanFilter;

BooleanFilter::make('is_active')
```

In some cases you can have a `nullable` column, the BooleanFilter can handle nulls in different ways:

- Hide nulls
- Show nulls
- Treats nulls as True
- Treats nulls as False

By default `nulls` are hidden

```php
BooleanFilter::make('is_active'')->showNulls();
BooleanFilter::make('is_active'')->hideNulls();
BooleanFilter::make('is_active'')->nullsAreTrue();
BooleanFilter::make('is_active'')->nullsAreFalse();
```

### DateFilter

Filter records by date/timestamp column:

```php
use Webbingbrasil\FilamentAdvancedFilter\Filters\DateFilter;

DateFilter::make('published_at')
```

This filter allows user to search records in some conditions:

- Is equal/not equal to user's input
- Is on or after/before user's input
- Is more than/less than user's input
  
    the user is has the option to choose a time period in the future or the past, for example:
  - more than 3 days from now
  - more than 4 months ago
  - less than 5 weeks from now
  - less than 6 days ago

- Is between user's input
- Is set/not set


### NumberFilter

Filter records by numeric column:

```php
use Webbingbrasil\FilamentAdvancedFilter\Filters\NumberFilter;

NumberFilter::make('quantity')
```

This filter allows user to search records in some conditions:

- Is equal/not equal to user's input
- Is on or after/before user's input
- Is more than/less than user's input
- Is between user's input
- Is set/not set

## Credits

-   [Danilo Andrade](https://github.com/dmandrade)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
