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
  'http://www.swinginfreiburg.de',
  'http://shop.swinginfreiburg.de',
  'http://dev.swinginfreiburg.de',
  'http://test.swinginfreiburg.de',
]);
$installation_dev->setDocroot('/var/www/virtual/jenn/installations/swif-live/docroot');
$project->addInstallation($installation_dev);

$live = new Freistilbox('c145', 's1890');
$project->addInstallation(new Installation('live', $live, [
  'http://www.boost.swinginfreiburg.de',
  'http://shop.boost.swinginfreiburg.de',
]));

// Do not forget!
return $project;
