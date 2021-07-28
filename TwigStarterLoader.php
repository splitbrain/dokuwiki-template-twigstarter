<?php

namespace dokuwiki\template\twigstarter;

use Twig\Loader\FilesystemLoader;

/**
 * Custom loader that takes the DokuWiki config into account
 */
class TwigStarterLoader extends FilesystemLoader
{
    /**
     * Cache is dependent on DokuWiki config
     * @inheritdoc
     */
    public function isFresh($name, $time)
    {
        $fresh = parent::isFresh($name, $time);
        if (!$fresh) return $fresh;

        $ctime = @filemtime(DOKU_CONF . 'local.php');

        return ($time > $ctime);
    }
}
