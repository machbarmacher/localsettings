<?php
/**
 * @file mmm-project.php
 */

namespace clever_systems\mmm_builder;

use clever_systems\mmm_builder\ServerType\Freistilbox;
use clever_systems\mmm_builder\ServerType\Uberspace;

$project = new Project();

$project->addInstallation('dev', new Uberspace('norma', 'jenn'))
  ->addSite('default', 'http://shop.swinginfreiburg.de')
  ->addSite('live', 'http://www.swinginfreiburg.de')
  ->addSite('dev', 'http://dev.swinginfreiburg.de')
  ->addSite('test', 'http://test.swinginfreiburg.de')
  ->setDocroot('/var/www/virtual/jenn/installations/swif-live/docroot')
  ->setDbCredentialPattern('jenn_{{site}}');

// FSB: Default docroot, single database.
$project->addInstallation('live', new Freistilbox('c145', 's1890'))
  ->addSite('live', 'http://live.boost.swinginfreiburg.de');
$project->addInstallation('live-test', new Freistilbox('c145', 's1891'))
  ->addSite('live', 'http://live-test.boost.swinginfreiburg.de');
$project->addInstallation('shop', new Freistilbox('c145', 's1892'))
  ->addSite('default', 'http://shop.boost.swinginfreiburg.de');
$project->addInstallation('shop-test', new Freistilbox('c145', 's1892'))
  ->addSite('default', 'http://shop-test.boost.swinginfreiburg.de');

// Do not forget!
return $project;
