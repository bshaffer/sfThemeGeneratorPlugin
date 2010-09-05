<?php

class sfGenerateThemeTaskCleanup
{
  protected 
    $modules   = null,
    $templates = null;

  public function __construct()
  {
    $this->modules    = $this->getModules();
    $this->templates  = $this->getTemplates();
  }

  public function __destruct()
  {
    $this->cleanup();
  }

  public function cleanup()
  {
    // Clear any added modules since the construction of this class
    foreach (array_diff($this->getModules(), $this->modules) as $dir)
    {
      sfToolkit::clearDirectory($dir);
      rmdir($dir);
    }
    
    // Clear any added templates since the construction of this class
    foreach (array_diff($this->getTemplates(), $this->templates) as $file)
    {
      unlink($file);
    }
    
    // Clear routing.yml file  
    file_put_contents(sfConfig::get('sf_app_config_dir').'/routing.yml', '');
  }

  protected function getModules()
  {
    return sfFinder::type('dir')->maxdepth(0)
      ->in(sfConfig::get('sf_app_module_dir'));
  }
  
  protected function getTemplates()
  {
    return sfFinder::type('file')->maxdepth(0)
      ->in(sfConfig::get('sf_app_template_dir'));
  }
}