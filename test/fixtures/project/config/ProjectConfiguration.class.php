<?php

require_once $_SERVER['SYMFONY'].'/autoload/sfCoreAutoload.class.php';
sfCoreAutoload::register();

class ProjectConfiguration extends sfProjectConfiguration
{
  public function setup()
  {
    $this->setPlugins(array(
      'sfThemeGeneratorPlugin',
      'sfDoctrinePlugin',
    ));

    $this->setPluginPath('sfThemeGeneratorPlugin', $_SERVER['SYMFONY_PLUGINS_DIR'] . '/sfThemeGeneratorPlugin');
  }
}
