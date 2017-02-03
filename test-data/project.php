<?php
/**
 * @file project.php
 */

namespace clever_systems\mmm_builder;

use clever_systems\mmm_builder\InstallationType\Installation;
use clever_systems\mmm_builder\ServerType\Freistilbox;
use clever_systems\mmm_builder\ServerType\Uberspace;

$project = new Project();

$server_dev = new Uberspace('norma', 'jenn');
$installation_dev = new Installation('dev', $server_dev, [
  'http://shop.swinginfreiburg.de' => 'default',
  'http://www.swinginfreiburg.de' => 'live',
  'http://dev.swinginfreiburg.de' => 'dev',
  'http://test.swinginfreiburg.de' => 'test',
]);
$installation_dev->setDocroot('/var/www/virtual/jenn/installations/swif-live/docroot');
$installation_dev->setDbCredentialPattern('jenn_{{site}}');
$project->addInstallation($installation_dev);

// FSB: Default docroot, single database.
$project->addInstallation(new Installation('live', new Freistilbox('c145', 's1890'), [
  'http://live.boost.swinginfreiburg.de' => 'live',
]));
$project->addInstallation(new Installation('live-test', new Freistilbox('c145', 's1891'), [
  'http://live-test.boost.swinginfreiburg.de' => 'dev',
]));
$project->addInstallation(new Installation('shop', new Freistilbox('c145', 's1892'), [
  'http://shop.boost.swinginfreiburg.de' => 'default',
]));
$project->addInstallation(new Installation('shop-test', new Freistilbox('c145', 's1893'), [
  'http://shop-test.boost.swinginfreiburg.de' => 'test',
]));

// Do not forget!
return $project;
