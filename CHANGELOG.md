# Changelog since 2.1.0

* Better ordering of style rules and properties, thx to [Hikariii](https://github.com/Hikariii),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/160)
* Some Travis improvements, thx to [barryvdh](https://github.com/barryvdh),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/158)
* Refactor the storage of the computed properties, thx to [stof](https://github.com/stof),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/153)
* Use possessive quantifiers to avoid reaching PCRE limits, thx to [stof](https://github.com/stof),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/146)
* Reuse the same CssSelectorConverter, thx to [stof](https://github.com/stof),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/152)
* Add a test for the removal of comments, thx to [stof](https://github.com/stof),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/147)
* Add the PHP requirement in the composer.json, thx to [stof](https://github.com/stof),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/148)
* Fix the detection of style tags, thx to [stof](https://github.com/stof),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/149)
* Remove the useless Selector class, thx to [stof](https://github.com/stof),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/150)

# Changelog since 2.0.0

* Remove the charset in css-files before processing, thx to [mdio](https://github.com/mdio)
* Several fixes related to UTF8-encoding, thx to [techi602](https://github.com/techi602), 
    [josh18](https://github.com/josh18)
* php-syntax-highlighting in the examples, thx to [Big-Shark](https://github.com/Big-Shark)

# Changelog since 1.5.5

The 2.0 version is a major overhaul, which is *not* backwards compatible.

* From now on you can re-use the class for multiple mails.
* A lot less complicated options, as in: no more options at all.
* More separate classes which handle their own (tested) methods.
* A lot more tests

The reason why I did this was to made the class more usable.

# Changelog since 1.5.4

* Better README + License
* Use DOM instead of regexp in cleanupHTML()
* Use Selector class
* Fixed stripping of XML-header
* Converted $cssRules to a local variable returned by processCSS
* Allow Symfony/CssSelector 3.0
* Removed unneeded stability change

# Changelog since 1.5.3

* Fix properties split on base64 encoded url content, thx to [tguyard](https://github.com/Giga-gg),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/79)
* Reset the xml error handling after usage, thx to [stof](https://github.com/stof),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/80)
* Remove version from require, thx to [barryvdh](https://github.com/barryvdh),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/85)

# Changelog since 1.5.2

* Make sure the XML header is removed.

# Changelog since 1.5.1

* Refactor removing style tags, thx to [barryvdh](https://github.com/barryvdh),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/72)
* Set excludeMediaQueries to true as default, thx to [barryvdh](https://github.com/barryvdh),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/69)
* Normalised eof and removed extra whitespace, thx to [GrahamCampbell](https://github.com/GrahamCampbell),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/68)
* Testing Improvements, thx to [GrahamCampbell](https://github.com/GrahamCampbell),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/70)
* Add note about charset, thx to [barryvdh](https://github.com/barryvdh),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/73)
* Deprecate encoding, thx to [barryvdh](https://github.com/barryvdh),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/74)
* Added a .gitattributes file, thx to [GrahamCampbell](https://github.com/GrahamCampbell),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/71)

# Changelog since 1.5.0

* Exclude vendor files from coverage, thx to [barryvdh](https://github.com/barryvdh),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/67)
* Add tests for Specificity and !important, thx to [barryvdh](https://github.com/barryvdh),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/65)


# Changelog since 1.4.4

* Made the class compliant with PSR4

# Changelog since 1.4.3

* Removed the .lock-file for real.

# Changelog since 1.4.2

* Refactor specificity, thx to [barryvdh](https://github.com/barryvdh),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/59)

# Changelog since 1.4.1

* Ignore the composer.lock-file as it doesn't make any sense
* Tests. Massive thumbs up for [jbboehr](https://github.com/jbboehr),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/61)
* Tweak for the `!important` attributes-fix, thx to [barryvdh](https://github.com/barryvdh),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/62)

# Changelog since 1.4.0

* Skip `!important` attributes if needed, thx to [barryvdh](https://github.com/barryvdh),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/58)

# Changelog since 1.3.0

* Use Xpath instead of regex, thx to [stof](https://github.com/stof),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/52)

# Changelog since 1.2.1

* sortOnSpecifity is now working correctly, thx to [lifo101](https://github.com/lifo101),
    for more information see the [Pull Request](https://github.com/tijsverkoyen/CssToInlineStyles/pull/37)

# Changelog since 1.2.0

* introduced a flag to remove media queries before inlining. thx to [Stof](https://github.com/stof).

# Changelog since 1.1.0

* require php 5.3

# Changelog since 1.0.6

* made the class compliant with PSR2.

# Changelog since 1.0.5

* made the class available through composer

# Changelog since 1.0.4

* beter handling of XHTML output, thx to Michele Locati.
* preserve original styles.

# Changelog since 1.0.3

* fixed some code-styling issues
* added support for multiple values

# Changelog since 1.0.2

* .class are matched from now on.
* fixed issue with #id
* new beta-feature: added a way to output valid XHTML (thx to Matt Hornsby)
* added setEncoding() to indicate the encoding

# Changelog since 1.0.1

* fixed some stuff on specifity

# Changelog since 1.0.0

* rewrote the buildXPathQuery-method
* fixed some stuff on specifity
* added a way to use inline style-blocks
