<?php

use dokuwiki\template\twigstarter\TemplateController;

$TemplateController = new TemplateController(basename(__FILE__, '.php'));
$TemplateController->render();
