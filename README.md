# CssToInlineStyles class

[![Build Status](https://travis-ci.org/tijsverkoyen/CssToInlineStyles.svg?branch=master)](https://travis-ci.org/tijsverkoyen/CssToInlineStyles) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/tijsverkoyen/CssToInlineStyles/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/tijsverkoyen/CssToInlineStyles/?branch=master) [![Code Coverage](https://scrutinizer-ci.com/g/tijsverkoyen/CssToInlineStyles/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/tijsverkoyen/CssToInlineStyles/?branch=master) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/5c0ce94f-de6d-403e-9e0a-431268deb75c/mini.png)](https://insight.sensiolabs.com/projects/5c0ce94f-de6d-403e-9e0a-431268deb75c)

## Installation

> CssToInlineStyles is a class that enables you to convert HTML-pages/files into
> HTML-pages/files with inline styles. This is very usefull when you're sending
> emails.

## About

PHP CssToInlineStyles is a class to convert HTML into HTML with inline styles.

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
