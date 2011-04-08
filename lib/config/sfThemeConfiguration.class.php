<?php

class sfThemeConfiguration
{
  protected 
    $task       = null,
    $theme      = null,
    $options    = array(),
    $constants  = array();

  public function __construct(sfTask $task, $options = array())
  {
    $this->task = $task;
    $this->options = $options;
  }
  
  // Method to prompt users for your theme's options
  public function setup()
  {}
  
  // Optional method to perform options after task has run
  public function cleanup()
  {}
  
  public function execute()
  {
    $this->addThemeRoutes();
    
    // Copy over files specified in theme configuration
    $this->doCopy();
    
    // replate php and yml constants
    $moduleDir = sfConfig::get('sf_app_module_dir').'/'.$this->options['module'];
    $finder = sfFinder::type('file')->name('*.php', '*.yml');
    $this->getFilesystem()->replaceTokens($finder->in($moduleDir), '##', '##', $this->getConstants());
  }
  
  public function filesToCopy()
  {
    return array(
      'MODULE_DIR' => 'THEME_DIR/skeleton'
    );
  }
  
  public function addThemeRoutes()
  {
    // Write new route to application's routing.yml
    $routing = sfConfig::get('sf_app_config_dir').'/routing.yml';
    $content = file_get_contents($routing);
    $routesArray = sfYaml::load($content);
    $routeContent = '';
    
    foreach ($this->routesToPrepend() as $name => $route) 
    {
      if (isset($routesArray[$name]))
      {
        $this->task->logBlock(sprintf('Unable to add routes: route "%s" already exists', $this->options['module']), 'COMMENT');
      }
      else
      {
        $routeContent = sprintf("%s:\n%s\n\n%s", $name, $route, $routeContent);
      }
    }
    
    // Add at top of routing.yml file
    if ($routeContent) 
    {
      $content = $routeContent.$content;

      $this->task->logSection('file+', $routing);

      if (false === file_put_contents($routing, $content))
      {
        throw new sfCommandException(sprintf('Unable to write to file, %s.', $routing));
      }
    }
  }
  
  public function doCopy()
  {
    if (!$this->theme) 
    {
      throw new sfException(
        sprintf('No theme for class %s: You must set the protected $theme property in your sfThemeConfiguration subclass.', get_class($this))
      );
    }

    // Copy over files in theme
    $finder = sfFinder::type('any')->discard('.sf');
    
    if(!$dir = $this->task->getThemeDir($this->theme, 'sfDoctrineModule'))
    {
      throw new LogicException("Theme '$this->theme' not found");
    }

    foreach ($this->filesToCopy() as $to => $from)
    {
      
      $fromFile = strtr($from, $this->getConstants());
      $toFile = strtr($to, $this->getConstants());

      if (is_dir($fromFile)) 
      {
        $this->getFilesystem()->mkdirs($toFile);
        $this->getFilesystem()->mirror($fromFile, $toFile, $finder);
      }
      elseif (!file_exists($toFile))
      {
        $this->task->logSection('file+', $toFile);
        $this->getFilesystem()->copy($fromFile, $toFile);
      }
      else
      {
        $this->task->logBlock(sprintf('Unable to add file: "%s" already exists', $toFile), 'COMMENT');
      }
    }
  }
  
  public function routesToPrepend()
  {
    return array();
  }
  
  public function getConstants()
  {
    if (!$this->constants) 
    {
      $this->initConstants();
    }
    return $this->constants;
  }
  
  public function initConstants()
  {
    $propertiesPath = sfConfig::get('sf_config_dir').'/properties.ini';
    $properties     = file_exists($propertiesPath) ? parse_ini_file($propertiesPath, true) : array();
    
    $this->constants = array(
      'PROJECT_NAME'   => isset($properties['symfony']['name']) ? $properties['symfony']['name'] : 'symfony',
      'APP_NAME'       => $this->options['application'],
      'MODULE_NAME'    => $this->options['module'],
      'UC_MODULE_NAME' => ucfirst($this->options['module']),
      'MODEL_CLASS'    => $this->options['model'],
      'AUTHOR_NAME'    => isset($properties['symfony']['author']) ? $properties['symfony']['author'] : 'Your name here',
      'MODULE_DIR'     => sfConfig::get('sf_app_module_dir') . '/' . $this->options['module'],
      'MODULES_DIR'    => sfConfig::get('sf_app_module_dir'),
      'PROJECT_DIR'    => sfConfig::get('sf_root_dir'),
      'APP_DIR'        => sfConfig::get('sf_app_dir'),
      'APP_CONFIG_DIR' => sfConfig::get('sf_app_config_dir'),
      'THEME_DIR'      => $this->task->getThemeDir($this->theme, 'sfDoctrineModule'),
    );
  }
  
  protected function askForApplication($text = 'Application to generate theme', $default = null)
  {
    if (!isset($this->options['application']))
    {
      $this->options['application'] = $this->task->ask($text, null, $default);
    }

    while (!$this->options['application'] || !is_dir(sfConfig::get('sf_apps_dir').'/'.$this->options['application']))
    {
      $this->task->logBlock('Application does not exist!', 'ERROR');
      $this->options['application'] = $this->task->ask($text, null, $default);
    }
  }
  
  protected function askForModel($text = 'Model for this theme', $default = null)
  {
    if (!isset($this->options['model']))
    {
      $this->options['model'] = $this->task->ask($text, null, $default);
    }

    if (!$this->options['model'] || !class_exists($this->options['model']))
    {
      $this->task->logBlock('Model does not exist!', 'ERROR');
      $this->options['model'] = $this->task->ask($text, null, $default);
    }
  }
  
  protected function askForOption($optionName, $text = null, $default = null, sfValidatorBase $validator = null)
  {
    $text = $text ? $text : sprintf('%s for this theme', sfInflector::humanize($optionName));
    
    $validator = $validator ? $validator : new sfValidatorPass();
    
    if (!isset($this->options[$optionName]))
    {
      $this->options[$optionName] = $this->task->ask($text, null, $default);
    }
    
    $pass = false;
    
    try
    {
      $validator->clean($this->options[$optionName]);
      $pass = true;
    }
    catch (sfValidatorError $error) {}

    while (!$pass)
    {
      $this->task->logBlock($error->getMessage(), 'ERROR');
      $this->options[$optionName] = $this->task->ask($text, null, $default);
      
      try
      {
        $validator->clean($this->options[$optionName]);
        $pass = true;
      }
      catch (sfValidatorError $error) {}
    }
  }
  
  public function filterGeneratedFile($file)
  {}
  
  protected function getFilesystem()
  {
    return $this->task->getFilesystem();
  }
}