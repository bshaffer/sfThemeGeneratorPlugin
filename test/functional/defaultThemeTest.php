<?php

// test the process of viewing and saving editable areas
require_once dirname(__FILE__).'/../bootstrap/bootstrap.php';

Doctrine_Query::create()->from('Company')->delete()->execute();
$company = new Company();
$company->name = 'Company Name';
$company->save();

$browser = new sfTestFunctionalTheme(new sfBrowser());

$browser->info('1. - Test generated module actions')
  ->info('  1.1 - Verify module does not exist')
    // ->cleanup()
  
  ->get('/company')

  ->with('response')->begin()
    ->isStatusCode(404)
  ->end()
  
  ->info('  1.2 - Run generate:theme task')
  
  ->runTask('sfThemeGenerateTask', array('theme' => 'default'), array('application' => 'frontend', 'model' => 'Company', 'module' => 'company'))
;
sfToolkit::clearDirectory(sfConfig::get('app_cache_dir'));
$browser
  ->info('  1.3 - We\'ve got ourselves a default theme module!')
  ->get('/company')
    ->isModuleAction('company', 'index')
;