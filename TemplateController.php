<?php

namespace dokuwiki\template\twigstarter;

use BadFunctionCallException;
use BadMethodCallException;
use dokuwiki\Menu\MenuInterface;
use Exception;
use RuntimeException;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

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
        $paths = [];
        if (is_dir(tpl_incdir() . 'templates')) {
            $paths[] = tpl_incdir() . 'templates';
        }
        $paths[] = __DIR__ . '/templates';
        $loader = new FilesystemLoader($paths);
        $cache = $conf['cachedir'] . '/twig';
        io_mkdir_p($cache);
        $this->twig = new Environment($loader, [
            'cache' => $conf['allowdebug'] ? false : $cache,
            'debug' => $conf['allowdebug'],
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
        $data['_SERVER'] = $_SERVER;

        // make this controller available in twig as TPL
        $data['TPL'] = $this;

        // add user supplied data
        $data = array_merge($data, $vars);

        // render the current view template
        try {
            echo $this->twig->render($this->view . '.twig', $data);
        } catch (Exception $e) {
            $msg = hsc($e->getMessage());
            if ($conf['allowdebug']) {
                $msg .= '<pre>' . hsc($e->getTraceAsString()) . '</pre>';
            }

            nice_die($msg);
        }
    }

    /**
     * Initializes and returns one of the menus
     *
     * @param string $type
     * @return MenuInterface
     */
    public function menu($type)
    {
        $class = '\\dokuwiki\\Menu\\' . ucfirst($type) . 'Menu';
        if (class_exists($class)) {
            return new $class();
        }

        throw new BadMethodCallException("No such menu $type");
    }

    /**
     * Initializes a new object
     *
     * This basically exposes the 'new' keyword to Twig. If the given class can't be found
     * the current template's namespace is prepended and the lookup is tried again.
     *
     * @param string $class
     * @param array $arguments
     * @return Object
     */
    public function newObj($class, $arguments = [])
    {
        global $conf;
        if (class_exists($class)) {
            $classname = $class;
        } else {
            $classname = '\\dokuwiki\\template\\' . $conf['template'] . '\\' . $class;
        }
        if (!class_exists($classname)) {
            throw new RuntimeException("No such class $class");
        }

        return new $classname(...$arguments);
    }

    /**
     * Calls a static method on the given class
     *
     * This exposes any static method to Twig. If the given class can't be found
     * the current template's namespace is prepended and the lookup is tried again.
     *
     * @param string $class
     * @param string $function
     * @param array $arguments
     * @return mixed
     */
    public function callStatic($class, $function, $arguments = [])
    {
        global $conf;
        if (class_exists($class)) {
            $classname = $class;
        } else {
            $classname = '\\dokuwiki\\template\\' . $conf['template'] . '\\' . $class;
        }
        if (!class_exists($classname)) {
            throw new RuntimeException("No such class $class");
        }

        if (!is_callable([$classname, $function])) {
            throw new BadMethodCallException("No such method $class::$function");
        }

        return call_user_func_array([$classname, $function], $arguments);
    }

    /**
     * Return the current view as set in the constructor
     *
     * @return string
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * We make all our functions available to the template as methods of this object
     *
     * We always need the functions to return their data, not print it so we use output buffering to
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

        throw new BadFunctionCallException("Function $name() does not exist");
    }

}
