<?php

namespace Msingi;

use Doctrine\DBAL\Types\Type;
use Msingi\Cms\View\Helper\CurrentRoute;
use Msingi\Cms\View\Helper\PageFragment;
use Msingi\Cms\View\Helper\PageMeta;
use Msingi\Cms\View\Helper\SettingsValue;
use Msingi\Cms\View\Helper\Url;
use Zend\Authentication\Adapter\DbTable;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class Module
 *
 * @package Msingi
 */
class Module implements AutoloaderProviderInterface
{
    /**
     * @param MvcEvent $e
     */
    public function onBootstrap(MvcEvent $e)
    {
        $serviceManager = $e->getApplication()->getServiceManager();

        $config = $serviceManager->get('Config');

        //
        $eventManager = $e->getApplication()->getEventManager();
        // route matching
        $eventManager->attach($serviceManager->get('Msingi\Cms\RouteListener'));
        // determine locale
        $eventManager->attach($serviceManager->get('Msingi\Cms\LocaleListener'));
        // http processing
        $eventManager->attach($serviceManager->get('Msingi\Cms\HttpListener'));

        $this->initLayouts($e);

        // Enable using of enum fields with Doctrine ORM
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $serviceManager->get('Doctrine\ORM\EntityManager');

        $platform = $entityManager->getConnection()->getDatabasePlatform();
        $platform->registerDoctrineTypeMapping('enum', 'string');

        // Register enum types
        if (isset($config['doctrine']['enums'])) {
            foreach ($config['doctrine']['enums'] as $enum => $className) {
                Type::addType($enum, $className);
            }
        }

        //
//        $eventManager = $entityManager->getEventManager();
//        $eventManager->addEventListener(array(\Doctrine\ORM\Events::postLoad), new InjectListener($serviceManager));
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * @return array
     */
    public function getViewHelperConfig()
    {
        return array(
            'invokables' => array(
                'assets' => 'Msingi\Cms\View\Helper\Assets',
                'headLess' => 'Msingi\Cms\View\Helper\HeadLess',
                'deferJs' => 'Msingi\Cms\View\Helper\DeferJs',

                'language' => 'Msingi\Cms\View\Helper\Language',
                'languageName' => 'Msingi\Cms\View\Helper\LanguageName',
                'locale' => 'Msingi\Cms\View\Helper\Locale',

                'date' => 'Msingi\Cms\View\Helper\Date',
                'relativeDate' => 'Msingi\Cms\View\Helper\RelativeDate',

                'selectOptions' => 'Msingi\Cms\View\Helper\SelectOptions',

                'imageAttachment' => 'Msingi\Cms\View\Helper\ImageAttachment',
                'fileAttachment' => 'Msingi\Cms\View\Helper\FileAttachment',

                'gravatar' => 'Msingi\Cms\View\Helper\Gravatar',

                '_' => 'Zend\I18n\View\Helper\Translate',
                '_p' => 'Zend\I18n\View\Helper\TranslatePlural',

                'excerpt' => 'Msingi\Cms\View\Helper\Excerpt',

                'configValue' => 'Msingi\Cms\View\Helper\ConfigValue',

                'formElementErrorClass' => 'Msingi\Cms\View\Helper\FormElementErrorClass',
            ),
            'factories' => array(
                'currentRoute' => function (ServiceLocatorInterface $helpers) {
                        $viewHelper = new CurrentRoute();
                        $viewHelper->setServiceLocator($helpers->getServiceLocator());
                        return $viewHelper;
                    },
                'fragment' => function (ServiceLocatorInterface $helpers) {
                        $services = $helpers->getServiceLocator();
                        $app = $services->get('Application');
                        return new PageFragment($app->getMvcEvent());
                    },
                'metaValue' => function (ServiceLocatorInterface $helpers) {
                        $services = $helpers->getServiceLocator();
                        $app = $services->get('Application');
                        return new PageMeta($app->getMvcEvent());
                    },
                'settingsValue' => function (ServiceLocatorInterface $helpers) {
                        $viewHelper = new SettingsValue();
                        $viewHelper->setServiceLocator($helpers->getServiceLocator());
                        return $viewHelper;
                    },
                'u' => function (ServiceLocatorInterface $helpers) {
                        $helper = new Url();
                        $helper->setRouter($helpers->getServiceLocator()->get('Router'));
                        $match = $helpers->getServiceLocator()->get('Application')->getMvcEvent()->getRouteMatch();
                        $helper->setRouteMatch($match);
                        return $helper;
                    },
            ),
        );
    }

    /**
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/',
                ),
            ),
        );
    }

    /**
     * @return array
     */
    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                //
                'Msingi\Cms\ContentManager' => 'Msingi\Cms\Service\ContentManagerFactory',

                //
                'BackendAuthService' => 'Msingi\Cms\Service\Factory\BackendAuthService',
                'Msingi\Cms\Model\BackendAuthStorage' => function ($sm) {
                        return new AuthStorage();
                    },

                //
                'Msingi\Cms\Mailer\Mailer' => function ($sm) {
                        $mailer = new Cms\Mailer\Mailer();
                        $mailer->setServiceManager($sm);
                        return $mailer;
                    }
            ),
            'invokables' => array(
                // Event listeners
                'Msingi\Cms\RouteListener' => 'Msingi\Cms\RouteListener',
                'Msingi\Cms\HttpListener' => 'Msingi\Cms\HttpListener',
                'Msingi\Cms\LocaleListener' => 'Msingi\Cms\LocaleListener',
                // Settings form
                'Msingi\Cms\Form\Backend\SettingsForm' => 'Msingi\Cms\Form\Backend\SettingsForm',
                //
                'Settings' => 'Msingi\Cms\Settings',
            ),
        );
    }

    /**
     * @param MvcEvent $e
     */
    protected function initLayouts(MvcEvent $e)
    {
        $eventManager = $e->getApplication()->getEventManager();

        $eventManager->getSharedManager()->attach('Zend\Mvc\Controller\AbstractController', 'dispatch', function ($e) {
            $controller = $e->getTarget();
            $controllerClass = get_class($controller);
            $moduleNamespace = substr($controllerClass, 0, strpos($controllerClass, '\\'));
            $config = $e->getApplication()->getServiceManager()->get('config');
            if (isset($config['module_layouts'][$moduleNamespace])) {
                $controller->layout($config['module_layouts'][$moduleNamespace]);
            } else {
                $controller->layout('layout/' . strtolower($moduleNamespace) . '.phtml');
            }
        }, 100);

        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
    }
}