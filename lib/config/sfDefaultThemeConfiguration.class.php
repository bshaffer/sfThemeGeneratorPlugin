<?php

class sfDefaultThemeConfiguration extends sfThemeConfiguration
{
  protected
    $theme = 'default';
    
  public function setup()
  {
    $this->askForApplication();

    $this->askForModel();

    $this->task->bootstrapSymfony($this->options['application'], $this->options['env']);
    
    $this->askForOption('module', null, sfInflector::underscore($this->options['model']));
  }
  
  public function initConstants()
  {
    parent::initConstants();

    $this->constants['CONFIG'] = sprintf(<<<EOF
    model_class:           %s
    theme:                 %s
EOF
      ,
      $this->options['model'],
      $this->theme
    );
  }
  
  public function cleanup()
  {
    if (!isset($this->options['cache']) || !$this->options['cache']) 
    {
      // Copy over cache
      $copyCache = new sfThemeCopyCacheTask($this->task->getEventDispatcher(), $this->task->getFormatter());
      $copyCache->run(array('application' => $this->options['application'], 'module' => $this->options['module']), array('env' => $this->options['env'], 'force' => true));
    
      // Remove generator.yml
      $moduleDir = sfConfig::get('sf_app_module_dir').'/'.$this->options['module'];
      $this->getFilesystem()->remove($moduleDir.'/config/generator.yml');
      $this->getFilesystem()->remove($moduleDir.'/config');
    }
  }
  
  public function filterGeneratedFile($file)
  { 
    switch (true) 
    {
      // Rename class in actions.class.php
      case strpos($file, 'actions.class.php') !== false:
        $contents = file_get_contents($file);
        $search   = sprintf('auto%sActions', ucfirst($this->options['module']));
        $replace  = sprintf('%sActions', $this->options['module']);
        file_put_contents($file, str_replace($search, $replace, $contents));
        break;
    }
  }
}