<?php
/**
 * @file mmm-project.php
 */

namespace clever_systems\mmm_builder;

use clever_systems\mmm_builder\ServerType\FreistilboxServer;
use clever_systems\mmm_builder\ServerType\UberspaceServer;

$project = new Project(7);

$project->addInstallation('dev', new UberspaceServer('norma', 'jenn'))
  ->addSite('http://shop.swinginfreiburg.de', 'default')
  ->addSite('http://www.swinginfreiburg.de', 'live')
  ->addUri('http://fsds.dance', 'live')
  ->addSite('http://dev.swinginfreiburg.de', 'dev')
  ->addSite('http://test.swinginfreiburg.de', 'test')
  ->setDocroot('/var/www/virtual/jenn/installations/swif-live/docroot')
  ->setDbCredentialPattern('jenn_{{site}}');

// FSB: Default docroot, single database.
$project->addInstallation('live', new FreistilboxServer('c145', 's1890'))
  ->addSite('http://live.boost.swinginfreiburg.de', 'live');
$project->addInstallation('live-test', new FreistilboxServer('c145', 's1891'))
  ->addSite('http://live-test.boost.swinginfreiburg.de', 'live');
$project->addInstallation('shop', new FreistilboxServer('c145', 's1892'))
  ->addSite('http://shop.boost.swinginfreiburg.de', 'default');
$project->addInstallation('shop-test', new FreistilboxServer('c145', 's1892'))
  ->addSite('http://shop-test.boost.swinginfreiburg.de', 'default');

// Do not forget!
return $project;
