<?php

abstract class sfThemeConfiguration
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
  
  public function setup()
  {
    $this->askForApplication();

    $this->askForModel();

    $this->task->bootstrapSymfony($this->options['application'], $this->options['env']);
    
    $this->askForOption('module', null, sfInflector::underscore($this->options['model']));
  }
  
  public function execute()
  {
    $routing = sfConfig::get('sf_app_config_dir').'/routing.yml';
    $content = file_get_contents($routing);
    $routesArray = sfYaml::load($content);

    if (isset($routesArray[$this->options['module']]))
    {
      $this->task->logBlock(sprintf('Unable to add routes: route "%s" already exists', $this->options['module']), 'COMMENT');
    }
    else
    {
      $content = $this->routesToPrepend().$content;

      $this->task->logSection('file+', $routing);

      if (false === file_put_contents($routing, $content))
      {
        throw new sfCommandException(sprintf('Unable to write to file, %s.', $routing));
      }
    }
    
    $moduleDir = sfConfig::get('sf_app_module_dir').'/'.$this->options['module'];

    // create basic application structure
    $finder = sfFinder::type('any')->discard('.sf');
    
    if(!$dir = $this->task->getThemeDir($this->theme, 'sfDoctrineModule'))
    {
      throw new LogicException("Theme '$this->theme' not found");
    }

    // Copy over files specified in theme configuration
    // TODO: Move this into theme configuration

    foreach ($this->filesToCopy() as $from => $to) 
    {
      $fromFile = sprintf('%s/%s', $dir, $from);
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

    // replate php and yml constants
    $finder = sfFinder::type('file')->name('*.php', '*.yml');
    $this->getFilesystem()->replaceTokens($finder->in($moduleDir), '##', '##', $this->getConstants());
  }
  
  public function filesToCopy()
  {
    return array(
      'skeleton' => 'MODULE_DIR'
    );
  }
  
  public function routesToPrepend()
  {
    return '';
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
    $properties = parse_ini_file(sfConfig::get('sf_config_dir').'/properties.ini', true);
    
    $this->constants = array(
      'PROJECT_NAME'   => isset($properties['symfony']['name']) ? $properties['symfony']['name'] : 'symfony',
      'APP_NAME'       => $this->options['application'],
      'MODULE_NAME'    => $this->options['module'],
      'UC_MODULE_NAME' => ucfirst($this->options['module']),
      'MODEL_CLASS'    => $this->options['model'],
      'AUTHOR_NAME'    => isset($properties['symfony']['author']) ? $properties['symfony']['author'] : 'Your name here',
      'MODULE_DIR'     => sfConfig::get('sf_app_module_dir') . '/' . $this->options['module'],
      'MODULES_DIR'    => sfConfig::get('sf_app_module_dir'),
      'APP_DIR'        => sfConfig::get('sf_app_dir'),
      'APP_CONFIG_DIR' => sfConfig::get('sf_app_config_dir'),
    );
  }
  
  protected function askForApplication($text = 'Application to generate theme', $default = null)
  {
    if (!isset($this->options['application']))
    {
      $this->options['application'] = $this->task->ask($text, null, $default);
    }

    while (!is_dir(sfConfig::get('sf_apps_dir').'/'.$this->options['application']))
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

    while (!is_dir(sfConfig::get('sf_apps_dir').'/'.$this->options['application']))
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
  
  
  protected function getFilesystem()
  {
    return $this->task->getFilesystem();
  }
}