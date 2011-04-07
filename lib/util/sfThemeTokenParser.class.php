<?php

/**
*
*/
class sfThemeTokenParser
{
  protected
    $tokenMatches,
    $varName,
    $i18nCatalogue;

  function __construct($tokenMatches, $varName, $i18nCatalogue = null)
  {
    $this->setTokenMatches($tokenMatches);
    $this->varName       = $varName;
    $this->i18nCatalogue = $i18nCatalogue;
  }

  public function renderHtmlText($text)
  {
    if ($this->hasI18nEnabled()) {
      $text = sprintf('<?php echo __(\'%s\', array(), \''. $this->getI18nCatalogue().'\') ?>', $this->escapeString($text));
      return $this->replaceTokens($text, 'php');
    }

    return $this->replaceTokens($text, 'html');
  }

  // Render text in a PHP block
  public function renderPhpText($text)
  {
    $text = $this->replaceTokens($text, 'php');

    if ($this->hasI18nEnabled()) {
      $text = sprintf('__(%s, array(), \''. $this->getI18nCatalogue().'\')', $text);
    }

    return $text;
  }

  // Render text that will appear in a php array
  public function renderPhpArrayText($text)
  {
    if ($this->hasI18nEnabled()) {
      $text = sprintf('||__(\'%s\', array(), \''. $this->getI18nCatalogue().'\')||', $text);
    }

    return $text;
  }

  public function renderArray($array)
  {
    $arrayLines = array();
    foreach ($array as $key => $value) {
      $line = '  ';
      if (is_int($key)) {
        $line .= $key;
      }
      elseif (is_bool($key)) {
        $line .= $this->asPhp($key);
      }
      else {
        $line .= $this->renderPhpText($key);
      }
      
      $line .= ' => ';
      
      if (is_int($value)) {
        $line .= $value;
      }
      elseif (is_bool($value)) {
        $line .= $this->asPhp($value);
      }
      elseif(is_array($value)) {
        $line .= $this->renderArray($value);
      }
      else {
        $line .= $this->renderPhpText($value);
      }
      
      $arrayLines[] = $line;
    }
    
    return sprintf("array(%s)", implode(',', $arrayLines));
  }

  public function replaceTokens($string, $format = 'html')
  {
    $tr1 = array();
    $tr2 = array();
    $renderTextAsBlock = false;

    preg_match_all('/\'\|\|(.*?)\|\|\'/', $string, $matches, PREG_PATTERN_ORDER);

    if (count($matches[1])) {
      foreach ($matches[1] as $i => $name)
      {
        $tr1[$matches[0][$i]] = $this->unescapeString($name);
      }
    }

    preg_match_all('/%%([^%]+)%%/', $string, $matches, PREG_PATTERN_ORDER);

    if (count($matches[1])) {
      $renderTextAsBlock = false;

      foreach ($matches[1] as $i => $name)
      {
        if (isset($this->tokenMatches[$name])) {
          $tr2[$matches[0][$i]] = $this->tokenMatches[$name];
        }
        elseif($varName = $this->getVarName()) {
          $renderTextAsBlock = true;
          $getter  = $name == 'to_string' ? '$'.$varName : $this->getColumnGetter($name, $varName);
          $tr2[$matches[0][$i]]  = sprintf("'.%s.'", $getter);
        }
      }
    }

    switch ($format) {
      case 'html':
        if ($renderTextAsBlock) {
          $string = $this->renderTextInPhpBlock($this->escapeString($string));
        }
        break;

      case 'php':
        $string = $this->asPhp($string);
        break;
    }

    if ($tr1) {
      $string = strtr($string, $tr1);
    }

    if ($tr2) {
      $string = strtr($string, $tr2);
    }

    return $this->clearEmptyStrings($string);
  }

  public function clearEmptyStrings($text)
  {
    // start of string
    if (strpos($text, "''.") === 0) {
      $text = substr($text, 3);
    }

    // end of string
    if (strpos(strrev($text), "''.") === 0) {
      $text = strrev(substr(strrev($text), 3));
    }

    return strtr($text, array(
      ".''."           => '.',   // middle of string
      "(''."           => '(',   // start of function
      ", ''."          => ', ', // start array value
      "<?php echo ''." => '<?php echo ',  // start of php block
      ".'')"           => ')',   // end of function
      ".'',"           => ',',   // end of array value
      ".'' ?>"         => ' ?>',  // end of php block
    ));
  }

  public function renderTextInPhpBlock($text)
  {
    if (strpos($text, '<?php') !== 0) {
      $text = sprintf('<?php echo \'%s\' ?>', $text);
    }

    return $text;
  }

  public function escapeString($string)
  {
    return str_replace("'", "\\'", $string);
  }

  public function unescapeString($string)
  {
    return str_replace("\\'", "'", $string);
  }

  public function asPhp($variable)
  {
    return str_replace(array("\n", 'array ('), array('', 'array('), var_export($variable, true));
  }

  public function getColumnGetter($column, $varName = null)
  {
    $getter = 'get'.sfInflector::camelize($column);
    if ($varName)
    {
      $getter = sprintf('$%s->%s()', $this->getVarName(), $getter);
    }

    return $getter;
  }

  public function getVarName()
  {
    return $this->varName;
  }
  
  public function setTokenMatches($tokenMatches)
  {
    $this->tokenMatches = $tokenMatches;
  }
  
  public function getTokenMatches($tokenMatches)
  {
    return $this->tokenMatches;
  }

  public function hasI18nEnabled()
  {
    return isset($this->tokenMatches['i18n']) && $this->tokenMatches['i18n'];
  }

  public function getI18nCatalogue()
  {
    return $this->i18nCatalogue;
  }
}
