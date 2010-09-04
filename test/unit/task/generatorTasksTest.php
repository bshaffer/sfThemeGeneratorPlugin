<?php

include dirname(__FILE__).'/../../bootstrap/unit.php';

$cleanup = new sfGenerateThemeTaskCleanup();
$cleanup->cleanup();

$project_dir  = dirname(__FILE__) . '/../../fixtures/project';
$module_dir   = dirname(__FILE__) . '/../../fixtures/project/apps/frontend/modules';

$t = new sfGenerateThemeLimeTest();
$t->configuration = $configuration;

$t->diag('sfGenerateThemeTask');
$t->task_ok(array('theme' => 'test'), array('application' => 'frontend', 'model' => 'Company', 'module' => 'company'));

$t->ok(is_dir($module_dir.'/company'));
