<?php

require_once dirname(__FILE__).'/../bootstrap/bootstrap.php';

$t = new lime_test(null, new lime_output_color());

$tokens = array('real_token' => 'REAL_TOKEN');
$varname = 'testvar';
$parser = new sfThemeTokenParser($tokens, $varname);

$t->info('test "escapeString" function');
$t->is($parser->escapeString("they say 'love' is 'just a game'"), "they say \'love\' is \'just a game\'", 'string escaped successfully');

$t->info('test "unescapeString" function');
$t->is($parser->unescapeString("they say \'love\' is \'just a game\'"), "they say 'love' is 'just a game'", 'string unescaped successfully');

$t->info('test "clearEmptyStrings" function');
$t->is($parser->clearEmptyStrings('\'\'.$var'), '$var', 'clears empty strings from start of string');
$t->is($parser->clearEmptyStrings('$var.\'\''), '$var', 'clears empty strings from end of string');
$t->is($parser->clearEmptyStrings('\'\'.$var.\'\''), '$var', 'clears empty strings both start and end of string');
$t->is($parser->clearEmptyStrings('$var1.\'\'.$var2'), '$var1.$var2', 'clears empty strings from middle of string');
$t->is($parser->clearEmptyStrings('\'\'.$var1.\'\'.$var2.\'\''), '$var1.$var2', 'clears empty strings from start, end, and middle of string');

$t->info('test "renderTextInPhpBlock" function');
$t->is($parser->renderTextInPhpBlock('test'), '<?php echo \'test\' ?>', 'string is placed in a php echo block');
$t->is($parser->renderTextInPhpBlock($parser->renderTextInPhpBlock('test')), $parser->renderTextInPhpBlock('test'), 'string s already in blocks are not wrapped in additional block');

$t->info('test "replaceTokens" function');
$t->is($parser->replaceTokens('test'), 'test', 'string without token is unaffected');
$t->is($parser->replaceTokens('test %%real_token%%'), 'test REAL_TOKEN', 'strings containing token replaced successfully');
$t->is($parser->replaceTokens('test %%to_string%%'), '<?php echo \'test \'.$testvar ?>', 'special "to_string" token replaced successfully');
$t->is($parser->replaceTokens('test %%fake%%'), '<?php echo \'test \'.$testvar->getFake() ?>', 'getter token replaced successfully');
$t->is($parser->replaceTokens('test %%fake%%', 'php'), '\'test \'.$testvar->getFake()', 'returns string without PHP block wrapper');

$t->info('test "renderPhpText" function');
$t->is($parser->renderPhpText('test \'%%real_token%%\''), "'test \'REAL_TOKEN\''", 'string returned in php string format with token substitution');
$t->is($parser->renderPhpText('test \'%%to_string%%\''), "'test \''.\$testvar.'\''", 'string returned in php format with variable substitution');
$t->is($parser->renderPhpText("test %- '' '%%fake_token%%' %- %%real_token%% - %%to_string%%", 'php'), "'test %- \'\' \''.\$testvar->getFakeToken().'\' %- REAL_TOKEN - '.\$testvar", 'Massively complex substitution passed successfully');

$t->info('test "renderPhpArrayText" function');
$t->is($parser->renderArray(array('test' => 'test')), "array(  'test' => 'test')", 'simple array rendered successfully');
$t->is($parser->renderArray(array('test' => '%%real_token%%')), "array(  'test' => 'REAL_TOKEN')", 'simple array with token substitution rendered successfully');
$t->is($parser->renderArray(array('%%real_token%%' => 'test')), "array(  'REAL_TOKEN' => 'test')", 'simple array with token as array key rendered successfully');
$t->is($parser->renderArray(array('nested array' => array('%%real_token%%' => 'test', 'test' => '%%real_token%%'))), "array(  'nested array' => array(  'REAL_TOKEN' => 'test',  'test' => 'REAL_TOKEN'))", 'simple array with tokens in nested arrays rendered successfully');
$t->is($parser->renderArray(array('to_string' => '%%to_string%%')), "array(  'to_string' => \$testvar)", 'simple array with "to_string" token');
$t->is($parser->renderArray(array('fake_token' => '%%fake_token%%')), "array(  'fake_token' => \$testvar->getFakeToken())", 'simple array with getter token');
$t->is($parser->renderArray(array('fake_tokens' => '\'%%fake_token1%%\', \'%%fake_token2%%\'')), "array(  'fake_tokens' => '\''.\$testvar->getFakeToken1().'\', \''.\$testvar->getFakeToken2().'\'')", 'simple array with getter tokens mid string');
$t->is($parser->renderArray(array('fake_tokens' => '\'%%fake_token1%%\', \'%%fake_token2%%\'')), "array(  'fake_tokens' => '\''.\$testvar->getFakeToken1().'\', \''.\$testvar->getFakeToken2().'\'')", 'simple array with getter tokens mid string');

$t->info('test "replacePhpTokens" function');
$t->is($parser->replacePhpTokens("'||\$this->aFunction()||'"), '$this->aFunction()', 'php tokens in string replace value with an actual php stirng');