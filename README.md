# CssToInlineStyles class

[![CI](https://github.com/tijsverkoyen/CssToInlineStyles/actions/workflows/ci.yml/badge.svg)](https://github.com/tijsverkoyen/CssToInlineStyles/actions/workflows/ci.yml)

## About

CssToInlineStyles is a class that enables you to convert HTML-pages/files into
HTML-pages/files with inline styles. This is very useful when you're sending
emails.

## Installation

The recommended installation way is through [Composer](https://getcomposer.org).

```bash
$ composer require tijsverkoyen/css-to-inline-styles
```

## Example

```php
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

// create instance
$cssToInlineStyles = new CssToInlineStyles();

$html = file_get_contents(__DIR__ . '/examples/sumo/index.htm');
$css = file_get_contents(__DIR__ . '/examples/sumo/style.css');

// output
echo $cssToInlineStyles->convert(
    $html,
    $css
);
```

## Known issues

* no support for pseudo selectors
* no support for [css-escapes](https://mathiasbynens.be/notes/css-escapes)
* UTF-8 charset is not always detected correctly. Make sure you set the charset to UTF-8 using the following meta-tag in the head: `<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />`. _(Note: using `<meta charset="UTF-8">` does NOT work!)_

## Sites using this class

* [Each site based on Fork CMS](http://www.fork-cms.com)
* [Print en Bind](http://www.printenbind.nl)
* [Tiki Wiki CMS Groupware](http://sourceforge.net/p/tikiwiki/code/49505) (starting in Tiki 13)
* [Laravel Framework](https://github.com/laravel/framework/blob/v6.18.24/src/Illuminate/Mail/Markdown.php#L55)
