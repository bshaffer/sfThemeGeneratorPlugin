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
class sfGenerateModuleExtraTask extends sfDoctrineGenerateModuleTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addArguments(array(
      new sfCommandArgument('application', sfCommandArgument::REQUIRED, 'The application name'),
      new sfCommandArgument('route_or_model', sfCommandArgument::REQUIRED, 'The route name or the model class'),
    ));

    $this->addOptions(array(
      new sfCommandOption('module', null, sfCommandOption::PARAMETER_REQUIRED, 'The module name', null),
      new sfCommandOption('theme', null, sfCommandOption::PARAMETER_REQUIRED, 'The theme name', 'admin'),
      new sfCommandOption('singular', null, sfCommandOption::PARAMETER_REQUIRED, 'The singular name', null),
      new sfCommandOption('plural', null, sfCommandOption::PARAMETER_REQUIRED, 'The plural name', null),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('actions-base-class', null, sfCommandOption::PARAMETER_REQUIRED, 'The base class for the actions', 'sfActions'),
      new sfCommandOption('route-class', null, sfCommandOption::PARAMETER_REQUIRED, 'The routing collection class written to routing.yml', 'sfDoctrineRouteCollection'),
      new sfCommandOption('module-generator-class', null, sfCommandOption::PARAMETER_REQUIRED, 'The module generator class name', 'sfDoctrineModule'),
    ));

    $this->namespace = 'generate';
    $this->name      = 'module-extra';
    $this->aliases   = array('generate-module');
    $this->briefDescription = 'Generates an admin module';

    $this->detailedDescription = <<<EOF
The [doctrine:generate-admin|INFO] task generates a Doctrine admin module:

  [./symfony doctrine:generate-admin frontend Article|INFO]

The task creates a module in the [%frontend%|COMMENT] application for the
[%Article%|COMMENT] model.

The task creates a route for you in the application [routing.yml|COMMENT].

You can also generate a Doctrine admin module by passing a route name:

  [./symfony doctrine:generate-admin frontend article|INFO]

The task creates a module in the [%frontend%|COMMENT] application for the
[%article%|COMMENT] route definition found in [routing.yml|COMMENT].

For the filters and batch actions to work properly, you need to add
the [with_wildcard_routes|COMMENT] option to the route:

  article:
    class: sfDoctrineRouteCollection
    options:
      model:                Article
      with_wildcard_routes: true
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

      return $this->generateForRoute($arguments, $options);
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

    // Get Theme Configuration
    if ($options['theme'] && class_exists($configClass = sprintf('sf%sThemeConfiguration', $options['theme']))) 
    {
      $this->themeConfiguration = new $configClass(array_merge($arguments, $options));
    }
    else
    {
      $this->themeConfiguration = new sfModuleThemeConfiguration(array_merge($arguments, $options));
    }
    
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

    return $this->generateForRoute($arguments, $options);
  }
  
  protected function executeInit($arguments = array(), $options = array())
  {
    $moduleDir = sfConfig::get('sf_app_module_dir').'/'.$arguments['module'];

    // create basic application structure
    $finder = sfFinder::type('any')->discard('.sf');
    if($dir = $this->getThemeDir($options['theme'], $options['module-generator-class']))
    {
      // Copy over files specified in theme configuration
      // TODO: Move this into theme configuration
      $files = $this->themeConfiguration->filesToCopy();
      foreach ($files as $fromFile => $toFile) 
      {
        $fromFile = $dir.$fromFile;
        $toFile = strtr($toFile, array(
          '%module_dir%'      => $moduleDir,
          '%app_dir%'         => sfConfig::get('sf_app_dir'),
          '%app_config_dir%'  => sfConfig::get('sf_app_config_dir'),
          '%module%'          => $arguments['module'],
          '%model%'           => $arguments['model'],
        ));
        
        if (is_dir($toFile)) 
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
    non_verbose_templates: %s
    with_show:             %s
    singular:              %s
    plural:                %s
    route_prefix:          %s
    with_doctrine_route:   %s
    actions_base_class:    %s
EOF
    ,
      $arguments['model'],
      $options['theme'],
      $options['non-verbose-templates'] ? 'true' : 'false',
      $options['with-show'] ? 'true' : 'false',
      $options['singular'] ? $options['singular'] : '~',
      $options['plural'] ? $options['plural'] : '~',
      $options['route-prefix'] ? $options['route-prefix'] : '~',
      $options['with-doctrine-route'] ? 'true' : 'false',
      $options['actions-base-class']
    );
    $this->getFilesystem()->replaceTokens($finder->in($moduleDir), '##', '##', $this->constants);
  }
  
  
  public function getThemeDir($theme, $class)
  {
    $dirs = array_merge(
      array(sfConfig::get('sf_data_dir').'/generator/'.$class.'/'.$theme.'/skeleton'), // project
      $this->getPluginSubPaths('/data/generator/'.$class.'/'.$theme.'/skeleton')      // plugins
    );
    
    foreach ($dirs as $dir)
    {
      if (is_dir($dir))
      {
        return $dir;
      }
    }
  }
}
