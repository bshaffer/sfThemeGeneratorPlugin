<?php

/**
 * Generates a Doctrine admin module.
 *
 * @package    symfony
 * @subpackage doctrine
 * @version    SVN: $Id: sfDoctrineGenerateAdminTask.class.php 28809 2010-03-26 17:19:58Z Jonathan.Wage $
 */
class sfThemeCopyCacheTask extends sfThemeGenerateTask
{
  protected
    $options = array();

  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addArguments(array(
      new sfCommandArgument('application', sfCommandArgument::REQUIRED, 'The application the module resides in'),
      new sfCommandArgument('module', sfCommandArgument::REQUIRED, 'The name of the module to copy'),
    ));

    $this->addOptions(array(
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('force', 'f', sfCommandOption::PARAMETER_NONE, 'Overwrites all existing files'),
    ));

    $this->namespace = 'theme';
    $this->name      = 'copy-cache';

    $this->briefDescription = 'Copy generated cache into your module';

    $this->detailedDescription = <<<EOF
The [theme:copy-cache|INFO] task generates cache from a generator.yml and copies the 
cache file into the corresponding module.
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    // Verify Module is valid
    while (!is_dir(sfConfig::get('sf_app_module_dir').'/'.$arguments['module']))
    {
      $this->logBlock('Module does not exist!', 'ERROR');
      $arguments['module'] = $this->ask('The name of the module to copy');
    }
    
    // Load context
    $this->context = sfContext::createInstance($this->configuration);
    
    // Determine Paths
    $modulePath = sprintf('%s/%s', sfConfig::get('sf_app_module_dir'), $arguments['module']);
    $yamlPath   = sprintf('%s/%s', $modulePath, 'config/generator.yml');
    $cachePath  = sprintf('%s/auto%s', sfConfig::get('sf_module_cache_dir'), ucfirst($arguments['module']));

    // Verify module is valid
    if (!file_exists($yamlPath))
    {
      throw new InvalidArgumentException('The module specified does not exist in cache.  A generator.yml file is required.');
    }
    
    include($this->context->getConfigCache()->checkConfig($yamlPath));
    
    // Get theme configuration
    $config = sfYaml::load($yamlPath);

    $this->themeConfiguration = $this->getThemeConfiguration(isset($config['generator']['param']['theme']) ? $config['generator']['param']['theme'] : null);

    $this->setOptions($arguments, $options);
    $this->doCopy($cachePath, $modulePath);
    
    // Comment out generator.yml file
    file_put_contents($yamlPath, preg_replace('/(^|\n)/', '\1# ', file_get_contents($yamlPath)));
    $this->logSection('generator.yml', 'Commented out "generator.yml - no longer pulling from cache"');
  }
  
  public function doCopy($fromDir, $toDir)
  {
    // Copy over files in theme
    $files = sfFinder::type('file')->relative()->in($fromDir);

    foreach ($files as $file) 
    {
      $toFile = $toDir . '/' . $file;

      if (!file_exists($toFile) || $this->options['force'] || $this->ask(sprintf('file %s exists.  Overwrite? (y/n)', $file), null, 'n') == 'y') 
      {
        $this->logSection('file+', $toFile);
        $this->themeConfiguration->filterGeneratedFile($fromDir .'/'. $file);
        $this->getFilesystem()->copy($fromDir .'/'. $file, $toFile, array('override' => true));
      }
      else
      {
        $this->logBlock(sprintf('Unable to add file: "%s" already exists', $file), 'COMMENT');
      }
    }
  }
    
  protected function setOptions($arguments, $options)
  {
    $this->options = array_merge($arguments, $options);
  }
}