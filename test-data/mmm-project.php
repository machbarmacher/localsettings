<?php
/**
 * @file mmm-project.php
 */

namespace machbarmacher\localsettings;

use machbarmacher\localsettings\ServerType\FreistilboxServer;
use machbarmacher\localsettings\ServerType\UberspaceServer;

$project = new Project(7);

$project->addMultiEnvironment('dev', new UberspaceServer('rasalhague', 'clsys'))
  ->addSite('http://dev.clsys.rasalhague.uberspace.de', 'default')
  ->setDocroot('/var/www/virtual/clsys/installations/*/docroot')
  ->setDbCredentialPattern('{{installation}}');

// FSB: Default docroot, single database.
$project->addEnvironment('live', new FreistilboxServer('c145', 's1890'))
  ->addSite('http://live.boost.swinginfreiburg.de', 'live');
$project->addEnvironment('live-test', new FreistilboxServer('c145', 's1891'))
  ->addSite('http://live-test.boost.swinginfreiburg.de', 'live');
$project->addEnvironment('shop', new FreistilboxServer('c145', 's1892'))
  ->addSite('http://shop.boost.swinginfreiburg.de', 'default');
$project->addEnvironment('shop-test', new FreistilboxServer('c145', 's1892'))
  ->addSite('http://shop-test.boost.swinginfreiburg.de', 'default');

// Do not forget!
return $project;
