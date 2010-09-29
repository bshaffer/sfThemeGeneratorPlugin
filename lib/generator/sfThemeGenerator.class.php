<?php

class sfThemeGenerator extends sfDoctrineGenerator
{
  protected
    $options = array();

  public function get($config, $default = null)
  {
    return isset($this->options[$config]) ? $this->options[$config] : $default;
  }
  
  public function configToOptions($configs, $prefix = '')
  {
    foreach ($configs as $name => $config) 
    {
      $name = $prefix ? $prefix.'_'.$name : $name;
      if (is_array($config)) 
      {
        $this->configToOptions($config, $name);
      }

      $this->options[$name] = $config;
    }
  }
  
  protected function validateParameters($params)
  {
    parent::validateParameters($params);
    
    $this->configToOptions($this->config);
  }
}