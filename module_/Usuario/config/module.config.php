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

            'adms' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/usuarios',
                    'defaults' => array(
                        'controller' => 'Usuario\Controller\Usuario',
                        'action'     => 'usuarios',
                    ),
                ),
            ),

            'ingresaradm' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/usuarios/ingresar',
                    'defaults' => array(
                        'controller' => 'Usuario\Controller\Usuario',
                        'action'     => 'ingresar',
                    ),
                ),
            ),

            'saveajaxusuario' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/usuarios/saveajax',
                    'defaults' => array(
                        'controller' => 'Usuario\Controller\Usuario',
                        'action'     => 'saveajax',
                    ),
                ),
            ),

            'editaradm' => array(
                'type' => 'Segment',
                'options' => array(
                    'route'    => '/usuarios/editar[/:id]',
                    'defaults' => array(
                        'controller' => 'Usuario\Controller\Usuario',
                        'action'     => 'editar',
                    ),
                ),
            ),
            'saveeditarajax' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/usuarios/saveeditarajax',
                    'defaults' => array(
                        'controller' => 'Usuario\Controller\Usuario',
                        'action'     => 'saveeditarajax',
                    ),
                ),
            ),

            'usuarioChangeStatus' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/usuarios/change-status',
                    'defaults' => array(
                        'controller' => 'Usuario\Controller\Usuario',
                        'action'     => 'status',
                    ),
                ),
            ),

            'eliminaradm' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/usuarios/eliminar',
                    'defaults' => array(
                        'controller' => 'Usuario\Controller\Usuario',
                        'action'     => 'eliminar',
                    ),
                ),
            ),
            
            'viewsendmail' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/usuarios/viewsendmail',
                    'defaults' => array(
                        'controller' => 'Usuario\Controller\Usuario',
                        'action'     => 'viewsendmail',
                    ),
                ),
            ),
            
            'editarmisdatos' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/editarmisdatos',
                    'defaults' => array(
                        'controller' => 'Usuario\Controller\Usuario',
                        'action'     => 'editarmisdatos',
                    ),
                ),
            ),

            'editarMisdatosAjax' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/save-mis-datos',
                    'defaults' => array(
                        'controller' => 'Usuario\Controller\Usuario',
                        'action'     => 'editarmisdatosajax',
                    ),
                ),
            ),

            //TABLA USUARIOS CONTROLLER
            'tablausuarios'=>array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/usuarios/tabla-usuarios',
                    'defaults' => array(
                        'controller' => 'Usuario\Controller\Tablausuarios',
                        'action'     => 'lista',
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
            'Usuario\Controller\Usuario'        => 'Usuario\Controller\UsuarioController',
            'Usuario\Controller\Tablausuarios'  => 'Usuario\Controller\TablausuariosController'
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
            'usuarios/usuarios/usuarios' => __DIR__ . '/../view/usuarios/usuarios/usuarios.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ),
        'template_path_stack' => array(
            'Usuario' => __DIR__ . '/../view',
        ),
        'strategies'=>array(
            'ViewJsonStrategy'
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
        'Usuario' => array(
            'default' => 'layout/layout',
        )
    ),
);
