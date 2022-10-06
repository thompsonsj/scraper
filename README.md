# Sitemap scraper

Sitemap scraper is a Laravel 9 application designed to be run from the command line. 

It uses a given sitemap to parse HTML content for each URL and reports on attributes as specified in the command. Commands are designed to be as flexible as possible using CSS style selectors.

It can output to either the console or an Excel spreadsheet.

## Overview

The custom [Laravel Artisan Console](https://laravel.com/docs/artisan) `scraper` command can be used to audit content on either a local build of a website or on production.

Each command audits pages by pulling the sitemap, converting URLs to local, and parsing each page.

## Initial configuration

In your `.env` file:

Required:

- Set `SITEMAP_URL` to the URL of your sitemap. This can be local or on production. See following options.

Optional:

- Set `CONVERT_SITEMAP_TO_LOCAL_URLS` to `true` to replace all occurences of your production domain with your local domain in the sitemap. This is useful if your sitemap always reports production URLs, which is a common scenario.

`CONVERT_SITEMAP_TO_LOCAL_URLS` requires the following two settings to perform the string replacement:

- Set `PRODUCTION_DOMAIN` to the domain of your live site. e.g. `https://www.example.com`.
- Set `LOCAL_DOMAIN` to the domain of your local site. e.g. `http://localhost:3000`.

## Common options

Each command can accept the following options.

| Option | Description |
| ------ | ----------- |
| `--startsWith=` | Filter results by the start of each URL. Useful for locales, or specific directories. Forward slash not needed at the start. e.g. `--startsWith=se`. |
| `--output=xlsx` | Output results to an Excel spreadsheet file in the local `storage/app` directory. Unique filenames are generated for each report based on the date and selector, e.g. `2022-03-31-140453-h1-report.xlsx`. |

### Examples

```bash
# Report all h1 elements
php artisan scraper:selector "h1"
# Report all h1 elements in the Swedish locale an Excel spreadsheet
php artisan scraper:selector "h1" --locale=se --output=xlsx
```

## CSS Selector

Use the `php artisan scraper:selector` command to retrieve a report on elements based on a CSS selector.

Each result contains:

- **Element**: The HTML tag name.
- **Parent elements**: Parent HTML tag names in order.
- **Child elements**: Child HTML tag names in order.
- **Text**: Text content of the element.
- **Class**: Class names on the element. Note: Alternative attributes can be reported using the `--attrs` option.

### Options

In addition to the common options, you may pass an `attrs` argument of comma-separated values to retrieve additional attribute values on HTML elements. The default value is `class`.

| Option | Description |
| ------ | ----------- |
| `--attrs=` | Pass an `attrs` argument of comma-separated values to retrieve additional attribute values on HTML elements. The default value is `class`. e.g. `--attrs=class,data-test-id`. |
| `--showRelations` | Include this switch to show information about parent and child elements. |
| `--relationAttrs` | Pass a `relationAttrs` argument of comma-separated values to retrieve additional attribute values on relation HTML elements. The default value is `class`. e.g. `--relationAttrs=class,data-test-id`. |

```bash
php artisan scraper:selector "h1" --attrs=class,data-test-id
```

### More examples

Complex CSS selectors can be used.

#### Report all elements using one of Tailwind CSS's display utility classes

```bash
php artisan scraper:selector ".block, .inline-block, .inline, .flex"
```

#### Report on all meta descriptions

```bash
php artisan scraper:selector "meta[name='description']"  --attrs=content
```
