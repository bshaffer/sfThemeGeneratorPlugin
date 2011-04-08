<?php

class sfTestThemeConfiguration extends sfThemeConfiguration
{
  protected
    $theme = 'test';

  public function filesToCopy()
  {
    return array_merge(array(
      'APP_DIR/templates/custom.php' => 'THEME_DIR/templates/custom.php',
    ), parent::filesToCopy());
  }
  
  public function routesToPrepend()
  {
    $routes = array($this->options['module'].'_test_route' => sprintf(<<<EOF
  class: sfDoctrineRouteCollection
  options:
    model:                %s
    module:               %s
    prefix_path:          /%s
    column:               %s
    with_wildcard_routes: true
EOF
      ,
      $this->options['module'], 
      $this->options['model'], 
      $this->options['module'], 
      $this->options['module'],
      'id'
    ));

    return $routes;
  }
}