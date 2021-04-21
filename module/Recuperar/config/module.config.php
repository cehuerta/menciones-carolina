<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

return array(
    'router' => array(
        'routes' => array(

            'recuperar' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/recuperar',
                    'defaults' => array(
                        'controller'    => 'Recuperar\Controller\Recuperar',
                        'action'        => 'recuperar',
                    ),
                ),
            ),

            'codigo' => array(
                'type'    => 'Segment',
                'options' => array(
                    'route'    => '/recuperar/codigo[/:cod]',
                    'defaults' => array(
                        'controller'    => 'Recuperar\Controller\Recuperar',
                        'action'        => 'codigo',
                    ),
                ),
            ),

            
        ),
    ),
    'service_manager' => array(
        'abstract_factories' => array(
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Zend\Log\LoggerAbstractServiceFactory',
        ),
        'aliases' => array(
            'translator' => 'MvcTranslator',
        ),
    ),
    'translator' => array(
        'locale' => 'es_ES',
        'translation_file_patterns' => array(
            array(
                'type'     => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
            ),
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'Recuperar\Controller\Recuperar' => 'Recuperar\Controller\RecuperarController'
        ),
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => array(
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'recuperar/recuperar/recuperar' => __DIR__ . '/../view/recuperar/recuperar/recuperar.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ),
        'template_path_stack' => array(
            'Recuperar' => __DIR__ . '/../view',
        ),
    ),
    // Placeholder for console routes
    'console' => array(
        'router' => array(
            'routes' => array(
            ),
        ),
    ),
    'module_layouts' => array(
        'Recuperar' => array(
            'default' => 'layout/layout',
        )
    ),
);
