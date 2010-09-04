<?php

class sfAdminThemeConfiguration extends sfThemeConfiguration
{
  protected
    $theme = 'admin';

  public function filesToCopy()
  {
    return array(
      'skeleton/actions'                => 'MODULE_DIR/actions',
      'skeleton/config'                 => 'MODULE_DIR/config',
      'skeleton/templates'              => 'MODULE_DIR/templates',
      'skeleton/lib/helper.php'         => 'MODULE_DIR/lib/MODULE_NAMEGeneratorHelper.class.php',
      'skeleton/lib/configuration.php'  => 'MODULE_DIR/lib/MODULE_NAMEGeneratorConfiguration.class.php',
      'skeleton/lib/helper.php'         => 'MODULE_DIR/lib/MODULE_NAMEGeneratorHelper.class.php',
    );
  }
  
  public function initConstants()
  {
    parent::initConstants();

    $this->constants['CONFIG'] = sprintf(<<<EOF
      model_class:           %s
      theme:                 %s
      route_prefix:          %s
      actions_base_class:    %s
EOF
      ,
      $this->options['model'],
      $this->theme,
      $this->options['module'],
      'sfActions'
    );
  }
  
  public function routesToPrepend()
  {
    $primaryKey = Doctrine_Core::getTable($this->options['model'])->getIdentifier();
    $routes = array($this->options['module'] => sprintf(<<<EOF
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
      $primaryKey
    ));

    return $routes;
  }
}