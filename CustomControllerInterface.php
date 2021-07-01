<?php

namespace dokuwiki\template\twigstarter;

/**
 * Custom controller can be used by child templates and will be auto registered in Twig as SELF
 */
interface CustomControllerInterface
{
    /**
     * CustomControllerInterface constructor.
     * @param TemplateController $tpl The main template controller
     */
    public function __construct(TemplateController $tpl);
}
