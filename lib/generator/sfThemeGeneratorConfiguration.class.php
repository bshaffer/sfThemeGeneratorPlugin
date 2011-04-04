<?php

/**
* 
*/
abstract class sfThemeGeneratorConfiguration extends sfOldAndBustedGeneratorConfiguration
{
  /**
   * Constructor.
   */
  public function __construct()
  {
    $this->compile();
  }

  public function mergeFieldConfiguration($fields)
  {
    $defaults = $this->getFieldsDefault();
    foreach ($fields as $name => $fieldConfig) 
    {
      $fields[$name] = array_merge(
            isset($defaults[$name]) ? $defaults[$name] : array(),
            $fieldConfig);
            
      // Ensure every field has a default label
      if (!isset($fields[$name]['label'])) 
      {
        $fields[$name]['label'] = sfInflector::humanize($name);
      }
    }

    return $fields;
  }
  
  public function getFilterFields()
  {
    return $this->mergeFieldConfiguration($this->getFieldsFilter());
  }
  
  public function getConfiguration()
  {
    return $this->configuration;
  }
}
