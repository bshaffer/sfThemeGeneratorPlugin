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
    // get configuration for the given route
    if (false !== ($route = $this->getRouteFromName($arguments['route_or_model'])))
    {
      $arguments['route'] = $route;
      $arguments['route_name'] = $arguments['route_or_model'];
      
      return $this->executeInit($arguments, $options);
    }

    // is it a model class name
    if (!class_exists($arguments['route_or_model']))
    {
      throw new sfCommandException(sprintf('The route "%s" does not exist and there is no "%s" class.', $arguments['route_or_model'], $arguments['route_or_model']));
    }

    $r = new ReflectionClass($arguments['route_or_model']);
    if (!$r->isSubclassOf('Doctrine_Record'))
    {
      throw new sfCommandException(sprintf('"%s" is not a Doctrine class.', $arguments['route_or_model']));
    }

    // create a route
    $options['model'] = $arguments['route_or_model'];
    $options['name'] = strtolower(preg_replace(array('/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'), '\\1_\\2', $options['model']));

    if (isset($options['module']))
    {
      $options['route'] = $this->getRouteFromName($options['name']);
      if ($options['route'] && !$this->checkRoute($options['route'], $options['model'], $options['module']))
      {
        $options['name'] .= '_'.$options['module'];
      }
    }
    else
    {
      $options['module'] = $options['name'];
    }

    // Get Theme Configuration
    if (!class_exists($configClass = sprintf('sf%sThemeConfiguration', $options['theme']))) 
    {
      $configClass = 'sfDefaultThemeConfiguration';
    }
    
    $this->themeConfiguration = new $configClass(array_merge($arguments, $options));
    
    $databaseManager = new sfDatabaseManager($this->configuration);

    $routing = sfConfig::get('sf_app_config_dir').'/routing.yml';
    $content = file_get_contents($routing);
    $routesArray = sfYaml::load($content);

    if (!isset($routesArray[$options['name']]))
    {
      $content = $this->themeConfiguration->routesToPrepend().$content;

      $this->logSection('file+', $routing);

      if (false === file_put_contents($routing, $content))
      {
        throw new sfCommandException(sprintf('Unable to write to file, %s.', $routing));
      }
    }

    $arguments['route'] = $this->getRouteFromName($options['name']);
    $arguments['route_name'] = $options['name'];

    return $this->executeInit($arguments, $options);
  }
  
  protected function executeInit($arguments = array(), $options = array())
  {
    if (!$arguments['route'] instanceof sfDoctrineRouteCollection)
    {
      throw new sfCommandException(sprintf('The route "%s" is not a Doctrine collection route.', $arguments['route_name']));
    }

    $routeOptions = $arguments['route']->getOptions();

    if (!isset($arguments['module'])) 
    {
      $arguments['module'] = $routeOptions['module'];
    }
    
    if (!isset($arguments['model'])) 
    {
      $arguments['model'] = $routeOptions['model'];
    }
    
    $moduleDir = sfConfig::get('sf_app_module_dir').'/'.$arguments['module'];

    // create basic application structure
    $finder = sfFinder::type('any')->discard('.sf');
    if($dir = $this->getThemeDir($options['theme'], $options['module-generator-class']))
    {
      // Copy over files specified in theme configuration
      // TODO: Move this into theme configuration
      $files = $this->themeConfiguration->filesToCopy();
      foreach ($files as $from => $to) 
      {
        $fromFile = sprintf('%s/%s', $dir, $from);
        $toFile = strtr($to, array(
          '%module_dir%'      => sfConfig::get('sf_app_module_dir'),
          '%app_dir%'         => sfConfig::get('sf_app_dir'),
          '%app_config_dir%'  => sfConfig::get('sf_app_config_dir'),
          '%module%'          => $arguments['module'],
          '%model%'           => $arguments['model'],
        ));
      
        if (is_numeric($from)) 
        {
          // create directory or file
          $this->getFilesystem()->mkdirs($toFile);
        }
        elseif (is_dir($toFile)) 
        {
          $this->getFilesystem()->mirror($fromFile, $toFile, $finder);
        }
        elseif (!file_exists($toFile))
        {
          $this->getFilesystem()->rename($fromFile, $toFile);
        }
      }
    }
    else
    {
      throw new LogicException("Theme '$options[theme]' not found");
    }

    // create basic test
    $this->getFilesystem()->copy(sfConfig::get('sf_symfony_lib_dir').DIRECTORY_SEPARATOR.'task'.DIRECTORY_SEPARATOR.'generator'.DIRECTORY_SEPARATOR.'skeleton'.DIRECTORY_SEPARATOR.'module'.DIRECTORY_SEPARATOR.'test'.DIRECTORY_SEPARATOR.'actionsTest.php', sfConfig::get('sf_test_dir').DIRECTORY_SEPARATOR.'functional'.DIRECTORY_SEPARATOR.$arguments['application'].DIRECTORY_SEPARATOR.$arguments['module'].'ActionsTest.php');

    // customize test file
    $this->getFilesystem()->replaceTokens(sfConfig::get('sf_test_dir').DIRECTORY_SEPARATOR.'functional'.DIRECTORY_SEPARATOR.$arguments['application'].DIRECTORY_SEPARATOR.$arguments['module'].'ActionsTest.php', '##', '##', $this->constants);

    // customize php and yml files
    $finder = sfFinder::type('file')->name('*.php', '*.yml');
    $this->constants['CONFIG'] = sprintf(<<<EOF
    model_class:           %s
    theme:                 %s
    singular:              %s
    plural:                %s
    actions_base_class:    %s
EOF
    ,
      $arguments['model'],
      $options['theme'],
      $options['singular'] ? $options['singular'] : '~',
      $options['plural'] ? $options['plural'] : '~',
      $options['actions-base-class']
    );
    $this->getFilesystem()->replaceTokens($finder->in($moduleDir), '##', '##', $this->constants);
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
}
