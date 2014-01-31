<?php

namespace Msingi\Cms\Router;

use Zend\Mvc\Router\Http\RouteInterface;
use Zend\Mvc\Router\Http\RouteMatch;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\ArrayUtils;
use Zend\Stdlib\RequestInterface;

/**
 * Class StaticPage
 *
 * @package Msingi\Cms\Router
 */
class StaticPage implements RouteInterface, ServiceLocatorAwareInterface
{
    /**
     * @var array
     */
    protected $defaults = array();

    /**
     * @var null
     */
    protected $routePluginManager = null;

    /**
     * Create a new page route.
     *
     * @params array $defaults
     */
    public function __construct(array $defaults = array())
    {
        $this->defaults = array_merge(array(
            'controller' => 'frontend-page',
            'action' => 'page'
        ), $defaults);
    }

    /**
     * Match a given request.
     *
     * @param RequestInterface $request
     * @param int $pathOffset
     * @return null|RouteMatch|\Zend\Mvc\Router\RouteMatch
     */
    public function match(RequestInterface $request, $pathOffset = null)
    {
        /** @var string $path */
        $path = trim(substr($request->getUri()->getPath(), $pathOffset), '/');

        /** @var \Msingi\Cms\Model\Page $page */
        $page = $this->loadPage($path);
        if ($page == null)
            return null;

        /** @var array $routeParams */
        $routeParams = array_merge($this->defaults, array(
            'cms_page' => $page
        ));

        $routeMatch = new RouteMatch($routeParams, strlen($page->path));

        return $routeMatch;
    }

    /**
     * @param $path
     * @return \Msingi\Cms\Model\Page
     */
    protected function loadPage($path)
    {
        $serviceLocator = $this->routePluginManager->getServiceLocator();

        /** @var \Msingi\Cms\Db\Table\Pages $pagesTable */
        $pagesTable = $serviceLocator->get('Msingi\Cms\Db\Table\Pages');

        // 1 is root page always
        $parent_id = 1;
        foreach (explode('/', $path) as $slug) {
            /** @var \Msingi\Cms\Model\Page $page */
            $page = $pagesTable->fetchPage($slug, $parent_id);
            if ($page == null) {
                return null;
            }
            $parent_id = $page->id;
        }

        return $page;
    }

    /**
     * Assemble the route.
     *
     * @param array $params
     * @param array $options
     * @return string
     */
    public function assemble(array $params = array(), array $options = array())
    {
        return $params['path'];
    }

    /**
     * Get a list of parameters used while assembling.
     *
     * @return array
     */
    public function getAssembledParams()
    {
        return array();
    }

    /**
     * Create a new route with given options.
     *
     * @param array $options
     * @return void|static
     * @throws InvalidArgumentException
     */
    public static function factory($options = array())
    {
        if ($options instanceof \Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        } elseif (!is_array($options)) {
            throw new InvalidArgumentException(__METHOD__ . ' expects an array or Traversable set of options');
        }

        if (!isset($options['defaults'])) {
            $options['defaults'] = array();
        }

        return new static($options['defaults']);
    }

    /**
     * Set service locator
     *
     * @param ServiceLocatorInterface $routePluginManager
     */
    public function setServiceLocator(ServiceLocatorInterface $routePluginManager)
    {
        $this->routePluginManager = $routePluginManager;
    }

    /**
     * Get service locator
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->routePluginManager;
    }
}