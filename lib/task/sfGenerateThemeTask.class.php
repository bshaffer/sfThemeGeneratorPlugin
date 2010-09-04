<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * Generates a Doctrine admin module.
 *
 * @package    symfony
 * @subpackage doctrine
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: sfDoctrineGenerateAdminTask.class.php 28809 2010-03-26 17:19:58Z Jonathan.Wage $
 */
class sfGenerateThemeTask extends sfDoctrineGenerateModuleTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addArguments(array(
      new sfCommandArgument('theme', sfCommandArgument::REQUIRED, 'The theme name'),
    ));

    $this->addOptions(array(
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
    ));

    $this->namespace = 'generate';
    $this->name      = 'theme';
    $this->aliases   = array('generate-theme');
    $this->briefDescription = 'Generates functionality in your project based on a theme';

    $this->detailedDescription = <<<EOF
The [generate:theme|INFO] task generates functionality according to a theme:

  [./symfony generate:theme default|INFO]

The theme will then prompt the user for the arguments it requires to create itself.
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    // Get Theme Configuration
    if (!class_exists($configClass = sprintf('sf%sThemeConfiguration', sfInflector::camelize($arguments['theme'])))) 
    {
      $configClass = 'sfDefaultThemeConfiguration';
    }
    
    $databaseManager = new sfDatabaseManager($this->configuration);

    $this->themeConfiguration = new $configClass($this, $this->commandManager->getOptionValues());

    $this->themeConfiguration->setup();

    $this->themeConfiguration->execute();
    
    $this->logSection('generate', 'Task complete.');
  }
  
  public function ask($question, $style = 'QUESTION', $default = null)
  {
    if ($default !== null) 
    {
      switch (true) 
      {
        case $default === true:
          $text = 'true';
          break;

        case $default === false:
          $text = 'false';
          break;
        
        default:
          $text = $default;
      }
      $question = sprintf('%s [%s]:', $question, $text);
    }

    return parent::ask($question, $style, $default);
  }
  
  public function getThemeDir($theme, $class)
  {
    $dirs = array_merge(
      array(sfConfig::get('sf_data_dir').'/generator/'.$class.'/'.$theme),            // project
      $this->configuration->getPluginSubPaths('/data/generator/'.$class.'/'.$theme)   // plugins
    );
    
    foreach ($dirs as $dir)
    {
      if (is_dir($dir))
      {
        return $dir;
      }
    }
  }

  protected function process(sfCommandManager $commandManager, $options)
  {
    $this->commandManager = new sfThemeCommandManager($commandManager);
    $this->commandManager->process($options);
    $commandManager->process($options);
    
    if (!$this->commandManager->isValid())
    {
      throw new sfCommandArgumentsException(sprintf("The execution of task \"%s\" failed.\n- %s", $this->getFullName(), implode("\n- ", $this->commandManager->getErrors())));
    }
  }

  // ==============================================
  // = From sfDoctrineGenerateAdminTask.class.php =
  // ==============================================

  protected function getRouteFromName($name)
  {
    $config = new sfRoutingConfigHandler();
    $routes = $config->evaluate($this->configuration->getConfigPaths('config/routing.yml'));

    if (isset($routes[$name]))
    {
      return $routes[$name];
    }

    return false;
  }

  /**
   * Checks whether a route references a model and module.
   *
   * @param mixed  $route  A route collection
   * @param string $model  A model name
   * @param string $module A module name
   *
   * @return boolean
   */
  protected function checkRoute($route, $model, $module)
  {
    if ($route instanceof sfDoctrineRouteCollection)
    {
      $options = $route->getOptions();
      return $model == $options['model'] && $module == $options['module'];
    }

    return false;
  }
  
  public function bootstrapSymfony($app, $env, $debug = true)
  {
    $configuration = ProjectConfiguration::getApplicationConfiguration($app, $env, $debug);

    $this->context = sfContext::createInstance($configuration);
  }
}
