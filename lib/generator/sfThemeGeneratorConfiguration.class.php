<?php

/**
* 
*/
abstract class sfThemeGeneratorConfiguration extends sfModelGeneratorConfiguration
{
  protected 
    $availableConfigs = array();

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
  
  public function validateConfig($config)
  {
    $this->checkConfigIsValid($config, $this->availableConfigs);
  }
  
  public function checkConfigIsValid($configs, $available)
  {
    if ($available !== array()) // all options pass for "array()"
    {
      foreach ($configs as $key => $config) 
      {
        if (!isset($available[$key])) 
        {
          throw new InvalidArgumentException(sprintf('Configuration key "%s" is invalid.', $key));
        }
      
        if (is_array($config) && is_array($available[$key])) 
        {
          $this->checkConfigIsValid($config, $available[$key]);
        }
      }
    }
  }
}
