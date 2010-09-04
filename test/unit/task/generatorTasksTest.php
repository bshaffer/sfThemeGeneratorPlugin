<?php

include dirname(__FILE__).'/../../bootstrap/unit.php';

$cleanup = new sfModuleGeneratorTaskCleanup();
$cleanup->cleanup();

$project_dir  = dirname(__FILE__) . '/../../fixtures/project';
$module_dir   = dirname(__FILE__) . '/../../fixtures/project/apps/frontend/modules';

$t = new sfModuleGeneratorLimeTest();
$t->configuration = $configuration;

$t->diag('sfModuleGenerateTask');
$t->task_ok(array('application' => 'frontend', 'route_or_model' => 'Company'), array('theme' => 'test'));
$t->ok(file_exists($module_dir.'/company'));
