<?php

/**
 * CSS to Inline Styles class
 *
 * This source file can be used to convert HTML with CSS into HTML with inline styles
 *
 * Known issues:
 * - no support for pseudo selectors
 *
 * The class is documented in the file itself. If you find any bugs help me out and report them. Reporting can be done by sending an email to php-css-to-inline-styles-bugs[at]verkoyen[dot]eu.
 * If you report a bug, make sure you give me enough information (include your code).
 *
 * License
 * Copyright (c) 2010, Tijs Verkoyen. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products derived from this software without specific prior written permission.
 *
 * This software is provided by the author "as is" and any express or implied warranties, including, but not limited to, the implied warranties of merchantability and fitness for a particular purpose are disclaimed. In no event shall the author be liable for any direct, indirect, incidental, special, exemplary, or consequential damages (including, but not limited to, procurement of substitute goods or services; loss of use, data, or profits; or business interruption) however caused and on any theory of liability, whether in contract, strict liability, or tort (including negligence or otherwise) arising in any way out of the use of this software, even if advised of the possibility of such damage.
 *
 * @author		Tijs Verkoyen <php-css-to-inline-styles@verkoyen.eu>
 * @version		1.0.0
 *
 * @copyright	Copyright (c) 2010, Tijs Verkoyen. All rights reserved.
 * @license		BSD License
 */
class CSSToInlineStyles
{
	/**
	 * The CSS to use
	 *
	 * @var	string
	 */
	private $css;


	/**
	 * The processed CSS rules
	 *
	 * @var	array
	 */
	private $cssRules;


	/**
	 * The HTML to process
	 *
	 * @var	string
	 */
	private $html;


	/**
	 * Should the generated HTML be cleaned
	 *
	 * @var	bool
	 */
	private $cleanup = false;


	/**
	 * Constructor
	 * Creates an instance, you could set the HTML and CSS here, or load it later.
	 *
	 * @return	void
	 * @param	string[optional] $html	The HTML to process
	 * @param	string[optional] $css	The CSS to use
	 */
	public function __construct($html = null, $css = null)
	{
		if($html !== null) $this->setHTML($html);
		if($css !== null) $this->setCSS($css);
	}


	/**
	 * Convert a CSS-selector into an xPath-query
	 *
	 * @return	string
	 * @param	string $selector	The CSS-selector
	 */
	private function buildXPathQuery($selector)
	{
		// @todo	implement http://plasmasturm.org/log/444/

		// redefine
		$selector = (string) $selector;

		// the CSS selector
		$cssSelector = array(	'/\s+>\s+/',					// E > F	matches any F element that is a child of an element E.
								'/(\w+)\s+\+\s+(\w+)/',			// E > F	matches any F element that is a child of an element E.
								'/\s+/',						// E F 		matches any F element that is a descendant of an E element.
								'/(\w+)?\#([\w\-]+)/e',			// #		matches id attributes
								'/(\w+|\*)?((\.[\w\-]+)+)/e'	// .		matches class attributes
							);

		// the xPath-equivalent
		$xPathQuery = array(	'/',
								'\\1/following-sibling::*[1]/self::\\2',
								'//',
								"(strlen('\\1') ? '\\1' : '*').'[@id=\"\\2\"]'",
								"(strlen('\\1') ? '\\1' : '*').'[contains(concat(\" \",@class,\" \"),concat(\" \",\"'.implode('\",\" \"))][contains(concat(\" \",@class,\" \"),concat(\" \",\"',explode('.',substr('\\2',1))).'\",\" \"))]'",
							);

		// return
		return (string) '//'. preg_replace($cssSelector, $xPathQuery, $selector);
	}


	/**
	 * Calculate the specifity for the CSS-selector
	 *
	 * @return	int
	 * @param	string $selector
	 */
	private function calculateCSSSpecifity($selector)
	{
		// cleanup selector
		$selector = str_replace(array('>', '+'), array(' > ', ' + '), $selector);

		// init var
		$specifity = 0;

		// split the selector into chunks based on spaces
		$chunks = explode(' ', $selector);

		// loop chunks
		foreach($chunks as $chunk)
		{
			// an ID is important, so give it a high specifity
			if(strstr($chunk, '#') !== false) $specifity += 1000;

			// classes are more important than a tag, but less important then an ID
			elseif(strstr($chunk, '.')) $specifity += 100;

			// anything else isn't that important
			else $specifity += 10;
		}

		// return
		return $specifity;
	}


	/**
	 * Cleanup the generated HTML
	 *
	 * @return	string
	 * @param	string $html	The HTML to cleanup
	 */
	private function cleanupHTML($html)
	{
		// remove classes
		$html = preg_replace('/(\s)+class="(.*)"(\s)+/U', ' ', $html);

		// remove IDs
		$html = preg_replace('/(\s)+id="(.*)"(\s)+/U', ' ', $html);

		// return
		return $html;
	}


	/**
	 * Converts the loaded HTML into an HTML-string with inline styles based on the loaded CSS
	 *
	 * @return	string
	 */
	public function convert()
	{
		// validate
		if($this->html == null) throw new CSSToInlineStylesException('No HTML provided.');
		if($this->css == null) throw new CSSToInlineStylesException('No CSS provided.');

		// load css
		$this->processCSS();

		// create new DOMDocument
		$document = new DOMDocument();

		// set error level
		libxml_use_internal_errors(true);

		// load HTML
		$document->loadHTML($this->html);

		// create new XPath
		$xPath = new DOMXPath($document);

		// any rules?
		if(!empty($this->cssRules))
		{
			// loop rules
			foreach($this->cssRules as $rule)
			{
				// init var
				$query = $this->buildXPathQuery($rule['selector']);

				// validate query
				if($query === false) continue;

				// search elements
				$elements = $xPath->query($query);

				// loop found elements
				foreach($elements as $element)
				{
					// init var
					$properties = array();

					// get current styles
					$stylesAttribute = $element->attributes->getNamedItem('style');

					// any styles defined before?
					if($stylesAttribute !== null)
					{
						// get value for the styles attribute
						$definedStyles = (string) $stylesAttribute->value;

						// split into properties
						$definedProperties = (array) explode(';', $definedStyles);

						// loop properties
						foreach($definedProperties as $property)
						{
							// validate property
							if($property == '') continue;

							// split into chunks
							$chunks = (array) explode(':', trim($property), 2);

							// validate
							if(!isset($chunks[1])) continue;

							// loop chunks
							$properties[$chunks[0]] = trim($chunks[1]);
						}
					}

					// add new properties into the list
					foreach($rule['properties'] as $key => $value) $properties[$key] = $value;

					// build string
					$propertyChunks = array();

					// build chunks
					foreach($properties as $key => $value) $propertyChunks[] = $key .': '. $value .';';

					// build properties string
					$propertiesString = implode(' ', $propertyChunks);

					// set attribute
					if($propertiesString != '') $element->setAttribute('style', $propertiesString);
				}
			}
		}

		// get the HTML
		$html = $document->saveHTML();

		// cleanup the HTML if we need to
		if($this->cleanup) $html = $this->cleanupHTML($html);

		// return
		return $html;
	}


	/**
	 * Process the loaded CSS
	 *
	 * @return	void
	 */
	private function processCSS()
	{
		// init vars
		$css = $this->css;

		// remove newlines
		$css = str_replace(array("\r", "\n"), '', $css);

		// replace double quotes by single quotes
		$css = str_replace('"', '\'', $css);

		// remove comments
		$css = preg_replace('|/\*.*?\*/|', '', $css);

		// remove spaces
		$css = preg_replace('/\s\s+/', ' ', $css);

		// rules are splitted by }
		$rules = explode('}', $css);

		// loop rules
		foreach($rules as $rule)
		{
			// split into chunks
			$chunks = explode('{', $rule);

			// invalid rule?
			if(!isset($chunks[1])) continue;

			// set the selectors
			$selectors = trim($chunks[0]);

			// get cssProperties
			$cssProperties = trim($chunks[1]);

			// split multiple selectors
			$selectors = (array) explode(',', $selectors);

			// loop selectors
			foreach($selectors as $selector)
			{
				// validate selector
				if(strstr($selector, ':') !== false) throw new CSSToInlineStylesException('Pseudo-selectors ('. $selector .') aren\'t allowed.');

				// cleanup
				$selector = trim($selector);

				// build an array for each selector
				$ruleSet = array();

				// store selector
				$ruleSet['selector'] = $selector;

				// process the properties
				$ruleSet['properties'] = $this->processCSSProperties($cssProperties);

				// calculate specifity
				$ruleSet['specifity'] = $this->calculateCSSSpecifity($selector);

				// add into global rules
				$this->cssRules[] = $ruleSet;
			}
		}

		// sort based on specifity
		if(!empty($this->cssRules)) usort($this->cssRules, array('CSSToInlineStyles', 'sortOnSpecifity'));
	}


	/**
	 * CSS-properties can be defined in a random order, the render engine will process them in a specified order
	 * this method will sort the properties on specifity
	 *
	 * @param unknown_type $propertyString
	 */
	private function processCSSProperties($propertyString)
	{
		// split into chunks
		$properties = (array) explode(';', $propertyString);

		// init var
		$pairs = array();

		// loop properties
		foreach($properties as $property)
		{
			// split into chunks
			$chunks = (array) explode(':', $property, 2);

			// validate
			if(!isset($chunks[1])) continue;

			// add to pairs array
			$pairs[trim($chunks[0])] = trim($chunks[1]);
		}

		// sort the pairs
		ksort($pairs);

		// return
		return $pairs;
	}


	/**
	 * Should the IDs and classes be removed?
	 *
	 * @return	void
	 * @param	bool[optional] $on
	 */
	public function setCleanup($on = true)
	{
		$this->cleanup = (bool) $on;
	}


	/**
	 * Set CSS to use
	 *
	 * @return	void
	 * @param	string $css		The CSS to use
	 */
	public function setCSS($css)
	{
		// @later	implement a way to set an URL
		// @later	implement a way to set multiple CSS files
		$this->css = (string) $css;
	}


	/**
	 * Set HTML to process
	 *
	 * @return	void
	 * @param	string $html
	 */
	public function setHTML($html)
	{
		$this->html = (string) $html;
	}


	/**
	 * Sort an array on the specifity element
	 *
	 * @return	int
	 * @param	array $e1	The first element
	 * @param	array $e2	The second element
	 */
	private static function sortOnSpecifity($e1, $e2)
	{
		// validate
		if(!isset($e1['specifity']) || isset($e2['specifity'])) return 0;

		// lower
		if($e1['specifity'] < $e2['specifity']) return -1;

		// higher
		if($e1['specifity'] > $e2['specifity']) return 1;

		// fallback
		return 0;
	}
}


/**
 * CSSToInlineStyles Exception class
 *
 * @author	Tijs Verkoyen <php-css-to-inline-styles@verkoyen.eu>
 */
class CSSToInlineStylesException extends Exception
{
}

?>