<?php

abstract class sfModuleThemeConfiguration
{
  protected 
    $options = array();

  public function __construct($options)
  {
    $this->options = $options;
  }
  
  public function filesToCopy()
  {
    return array(
      'skeleton' => '%module_dir%'
    );
  }
  
  public function routesToPrepend()
  {
    $primaryKey = Doctrine_Core::getTable($this->options['model'])->getIdentifier();
    $routes = sprintf(<<<EOF
%s:
  class: sfDoctrineRouteCollection
  options:
    model:                %s
    module:               %s
    prefix_path:          /%s
    column:               %s
    with_wildcard_routes: true


EOF
      ,
      $this->options['name'], 
      $this->options['model'], 
      $this->options['module'], 
      isset($options['plural']) ? $options['plural'] : $this->options['module'], 
      $primaryKey
    );

    return $routes;
  }
}