<?php

/**
*
*/
class sfThemeTokenParser
{
  protected
    $tokenMatches,
    $varName;

  function __construct($tokenMatches, $varName)
  {
    $this->setTokenMatches($tokenMatches);
    $this->varName       = $varName;
  }

  public function renderHtmlText($text)
  {
    return $this->replaceTokens($text, 'html');
  }

  // Render text in a PHP block
  public function renderPhpText($text)
  {
    return $this->replaceTokens($text, 'php');
  }

  public function renderArray($array)
  {
    $arrayLines = array();
    foreach ($array as $key => $value) {
      $line = '  ';

      $isAssociative = $this->arrayKeyIsAssociative($key, $array);

      if (!$isAssociative) {
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
      }

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

    return $this->replacePhpTokens(sprintf("array(%s)", implode(',', $arrayLines)));
  }

  public function replaceTokens($string, $format = 'html')
  {
    $tr = array();
    $renderTextAsBlock = false;

    preg_match_all('/%%([^%]+)%%/', $string, $matches, PREG_PATTERN_ORDER);

    if (count($matches[1])) {
      $renderTextAsBlock = false;

      foreach ($matches[1] as $i => $name)
      {
        if (isset($this->tokenMatches[$name])) {
          $tr[$matches[0][$i]] = $this->tokenMatches[$name];
        }
        elseif($varName = $this->getVarName()) {
          $renderTextAsBlock = true;
          $getter  = $name == 'to_string' ? '$'.$varName : $this->getColumnGetter($name, $varName);
          $tr[$matches[0][$i]]  = sprintf("'.%s.'", $getter);
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

    if ($tr) {
      $string = strtr($string, $tr);
    }

    return $this->clearEmptyStrings($string);
  }

  public function replacePhpTokens($string)
  {
    $tr = array();
    preg_match_all('/\'\|\|(.*?)\|\|\'/', $string, $matches, PREG_PATTERN_ORDER);

    if (count($matches[1])) {
      foreach ($matches[1] as $i => $name)
      {
        $tr[$matches[0][$i]] = $this->unescapeString($name);
      }
    }

    if ($tr) {
      $string = strtr($string, $tr);
    }

    return $string;
  }

  public function wrapPhpToken($string)
  {
    return sprintf('||%s||', $string);
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
  
  protected function arrayKeyIsAssociative($key, $array)
  {
    if (!is_int($key) || !isset($array[$key]) || count($array) < $key) {
      return false;
    }
    
    $i = 0;
    foreach ($array as $value) {
      if ($i == $key) {
        return true;
      }
      
      if ($i > $key) {
        return false;
      }
      $i++;
    }
    
    return false;
  }
}
