<?php

namespace dokuwiki\template\twigstarter;

require_once __DIR__ . '/vendor/autoload.php';

class TemplateController
{
    protected $view;
    protected $twig;

    /**
     * TemplateController constructor.
     * @param string $view The current view (main, detail, mediamanager)
     */
    public function __construct($view)
    {
        global $conf;

        // better compatibility
        header('X-UA-Compatible: IE=edge,chrome=1');

        // what view is currently displayed?
        $this->view = $view;

        // lookup templates in the twigstarter and the actual template
        $paths = [__DIR__ . '/templates'];
        if (is_dir(tpl_basedir() . '/templates')) {
            $paths[] = tpl_basedir() . '/templates';
        }
        $loader = new \Twig\Loader\FilesystemLoader($paths);
        $this->twig = new \Twig\Environment($loader, [
            'cache' => $conf['cachedir'],
        ]);

    }

    /**
     * Render the template for the current view
     *
     * @param array $vars optional additional variables to set
     */
    public function render($vars = [])
    {
        global $conf;

        // register all globals to be available in twig
        $data = $GLOBALS;

        // make this controller available in twig as TPL
        $data['TPL'] = $this;

        // add user supplied data
        $data = array_merge($data, $vars);

        // render the current view template
        try {
            echo $this->twig->render($this->view . '.twig', $data);
        } catch (\Exception $e) {
            $msg = hsc($e->getMessage());
            if ($conf['allowdebug']) {
                $msg .= '<pre>' . hsc($e->getTraceAsString()) . '</pre>';
            }

            nice_die($msg);
        }
    }

    /**
     * We make all our functions available to the template as methods of this object
     *
     * We always need the function to return their data, not print it so we use output buffering to
     * catch any possible output.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (function_exists($name)) {
            ob_start();
            $return = call_user_func_array($name, $arguments);
            $output = ob_get_clean();
            if ($output !== '' && $output !== false) {
                return $output;
            }
            return $return;
        }

        throw new \BadFunctionCallException("Function $name() does not exist");
    }


}
