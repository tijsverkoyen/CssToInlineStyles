<?php
  /** A more advanced (or resiliant) version of the CSS inliner.
   *
   * Uses a modified version of the CSS to Xpath code ripped from the symfony project which in itself 
   * is a port of the Python lxml library to PHP.  All BSD licence I believe or near enough to.
   *
   * This produces much better CSS to Xpath mapping than the simple reg-exp approach taken in the
   * basic version of the inliner which often did not work as expected, or at all.
   *
   * An additional feature of this advanced version is that if no CSS is provided, the HTML is 
   * scanned for <link> elements and the CSS brought in from there, also @import in any linked
   * CSS is imported. It also parses inline <style> blocks by default.
   *
   * This version can be passed html and css on the constructor as for the normal version, 
   * but can also take a file path/url - new symfonyInliner(null, null, 'http://.......')
   * and will grab html from there if it wasn't provided.
   *
   * Even if you provide html, it's a good idea to provide the file path/url so that 
   * urls in the CSS can be resolved correctly.
   *
   * NB: The original Symfony package requires PHP 5.3, I have stripped the namespace 
   * usage out of the files and a cursory glance indicates to me it should function on 5.2 
   * and maybe less, but this is to-be-tested.
   *  
   * @author James Sleeman, Gogo Internet Services Limited <james@gogo.co.nz>   
   */
  
  
  require_once(dirname(__FILE__) .'/css_to_inline_styles.php');
  require_once(dirname(__FILE__) . '/CssSelector/CssSelector.php');
  
  class symfonyInliner extends CSSToInlineStyles
  {
    // When we are getting CSS files, we cache them here
    protected static $CSSCache = array();
    
    // These 3 are for infinite loop protection, increase them if you really need to
    protected static $MaximumImportDepth = 10;
    protected static $MaximumLinksPerHtml = 20;
    protected static $MaximumImportsPerCss = 20;
    
    public function __construct($html = null, $css = null, $File = null)
    {
      if(!$html && $File)
      {
        $html = file_get_contents($File);
      }
      
      // Set a default file so we can at least attempt to resolve relative URLs
      if(!isset($File)) 
      {
        $File = 'http://'.$_SERVER['HTTP_HOST'].'/index.html';
      }
            
      // If we have not been supplied CSS specifically, but have been supplied 
      // $html and/or $File
      //   process the file to find <link> and <style> and @import statements
      
      if(!$css && $html)
      {
        $css = '';
        
        // First Handle <link>, for each one, resolve the url in case it was relative to $File
        // and then pull the CSS in from it and nuke the <link>
        $MaxLink = symfonyInliner::$MaximumLinksPerHtml;        
        while($MaxLink-- && preg_match('/<link[^>]*\srel="stylesheet"[^>]*>/i', $html, $M))
        {
          if(preg_match('/href="([^"]+)/i', $M[0], $M2))
          {
            $LinkCSS = $this->get_css_file($this->resolve_url($M2[1], $File));         
          }
          else
          {
            $LinkCSS = '/* No Link Found */';
          }
          $css .= $LinkCSS . "\n\n";
          $html = str_replace($M[0], ' ', $html);        
        }
        
        // Then handle <style>, for each one process the CSS to handle import etc
        // and nuke the <style>
        $matches = array();
        preg_match_all('|<style(.*)>(.*)</style>|isU', $html, $matches);
        if(!empty($matches[2]))
        {
          // add
          foreach($matches[2] as $i => $match)
          {
            $css .= $this->pre_process_css($match, $File) . "\n\n";
            $html = str_replace($matches[0][$i], '', $html);
          }
        }
        
      }
      
      parent::__construct($html, $css);
      
    }

    /** Get a CSS file and return it's contents after processing for @import and url() 
     *  @param string File path/url to css
     *  @param integer Infinite recursion protection, leave NULL
     *  @return string CSS
     */
    protected function get_css_file($File, $MaxDepth = NULL)
    {
      if(!isset($MaxDepth)) $MaxDepth = symfonyInliner::$MaximumImportDepth;
      if(!$MaxDepth) return '/* Infinite Loop */'; // Loop protection
      
      if(isset(symfonyInliner::$CSSCache[$File])) return symfonyInliner::$CSSCache[$File];

      $Contents      = file_get_contents($File);
      if(!$Contents) return symfonyInliner::$CSSCache[$File] = '/* No Contents */';
      
      // Process the CSS to resolve URLs and resolve @imports
      $Contents = $this->pre_process_css($Contents, $File, $MaxDepth--);
      
      return symfonyInliner::$CSSCache[$File] = $Contents;
    }
    
    /** Naievly resolve a URL (as found in an @import or url() or link's href) in case
     *  it was relative.
     *
     * @param string URL to be resolved
     * @param string File path/url to which this url could be relative to
     */
     
    protected function resolve_url($Url, $RelativeToFile)
    {      
      if(preg_match('/^https?:\/\//i', $Url)) return; // Absolute URLs OK
      if(preg_match('/^\/\//', $Url)) return 'http:'.$Url; // Weird format absolute URLs (slashdot amongst others?)
      if(preg_match('/^\//', $Url)) // Absolute Files make into absolute URLs or absolute files from doc root
      {
        if(preg_match('/^https?:\/\/[^\/]+/i', $RelativeToFile, $FM))
        {
          return $FM[0] . $Url;
        }
        else
        {
          return $_SERVER['DOCUMENT_ROOT'] . $Url;
        }
      }
      else // Relative files make absolute
      {
        return dirname($RelativeToFile) . '/'. $Url;
      }      
    }
    
    /** Do some preprocessing on some CSS to correct URLs and handle @import
     *
     * @param String Css to process
     * @param string File path/url to which relative urls in @import and url() will be
     *                  resolved.  Defaults to http://[www.yourhost.com]/index.html
     *                  so that at least absolute links will work by default.
     * @param integer Protects against infinite recursion, leave it NULL.
     * @return string Processed CSS
     */
     
    protected function pre_process_css($Contents, $File, $MaxDepth = NULL)
    {           
      // Resolve all url() in the CSS
      preg_match_all('/url\([\'"]?([^\'"\)]+)/', $Contents, $M, PREG_SET_ORDER);
      foreach($M as $Set)
      {
        $FullUrl = $Set[0];
        $OldUrl  = $Set[1];
        $NewUrl  = $this->resolve_url($OldUrl, $File);
        
        if($NewUrl !== $OldUrl)
        {
          $NewFullUrl = str_replace($OldUrl, $NewUrl, $FullUrl);
          $Contents = str_replace($FullUrl, $NewFullUrl, $Contents);
        }
      }

      // Match import rules in the CSS source, these may or may not have url()      
      $MaxImport = symfonyInliner::$MaximumImportsPerCss; // Loop protection
      while($MaxImport-- && preg_match('/@import\s*(?:url\(?)?["\']?([^"\';]*)[^;]*;/i', $Contents, $M))
      {
        $FullUrl = $M[0];
        $OldUrl  = $M[1];
        $NewUrl  = $this->resolve_url($OldUrl, $File);
                        
        // At this point $NewUrl is either a full path or url, so grab that contents and 
        // replace the import statement with it
        $importcss = $this->get_css_file($NewUrl, $MaxDepth);
        $Contents = str_replace($FullUrl, $importcss, $Contents);    
      }
      
      return $Contents;
    }
    
    
    /**
    * Convert a CSS-selector into an xPath-query
    *
    * @return  string
    * @param string $selector  The CSS-selector.
    */
    protected function buildXPathQuery($selector)
    {
      try
      {
        $path = CssSelector::toXPath($selector);       
      }
      catch(ParseException $E)
      {
        $path = '';
      }   
     
      return $path;
    }

  }
?>