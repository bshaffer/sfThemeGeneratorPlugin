<?php

/**
* 
*/
abstract class sfThemeGeneratorConfiguration extends sfModelGeneratorConfiguration
{
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
}
