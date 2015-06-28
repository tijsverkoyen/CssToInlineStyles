# CssToInlineStyles class

[![Build Status](https://travis-ci.org/tijsverkoyen/CssToInlineStyles.svg?branch=master)](https://travis-ci.org/tijsverkoyen/CssToInlineStyles)

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

    use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

    // Convert HTML + CSS to HTML with inlined CSS
    $cssToInlineStyles= new CssToInlineStyles();
    $cssToInlineStyles->setHTML($html);
    $cssToInlineStyles->setCSS($css);
    $html = $cssToInlineStyles->convert();

    // Or use inline-styles blocks from the HTML as CSS
    $cssToInlineStyles = new CssToInlineStyles($html);
    $cssToInlineStyles->setUseInlineStylesBlock(true);
    $html = $cssToInlineStyles->convert();
    
    
## Documentation

The following properties exists and have set methods available:

Property | Default | Description
-------|---------|------------
cleanup|false|Should the generated HTML be cleaned?
useInlineStylesBlock |false|Use inline-styles block as CSS.
stripOriginalStyleTags |false|Strip original style tags.
excludeMediaQueries |true|Exclude media queries from extra "css" and keep media queries for inline-styles blocks.
excludeConditionalInlineStylesBlock |true|Exclude conditional inline-style blocks.


## Known issues

* no support for pseudo selectors
* UTF-8 charset is not always detected correctly. Make sure you set the charset to UTF-8 using the following meta-tag in the head: `<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />`. _(Note: using `<meta charset="UTF-8">` does NOT work!)_

## Sites using this class

* [Each site based on Fork CMS](http://www.fork-cms.com)
* [Print en Bind](http://www.printenbind.nl)
* [Tiki Wiki CMS Groupware](http://sourceforge.net/p/tikiwiki/code/49505) (starting in Tiki 13)
