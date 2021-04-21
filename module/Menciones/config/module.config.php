<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Menciones\Controller\Pautas'          => 'Menciones\Controller\PautasController',
            'Menciones\Controller\Clientes'        => 'Menciones\Controller\ClientesController',
            'Menciones\Controller\Campanas'        => 'Menciones\Controller\CampanasController',
            'Menciones\Controller\Menciones'       => 'Menciones\Controller\MencionesController',
            'Menciones\Controller\Locutor'         => 'Menciones\Controller\LocutorController',
            'Menciones\Controller\Horarios'        => 'Menciones\Controller\HorariosController',
            'Menciones\Controller\Categorias'      => 'Menciones\Controller\CategoriasController',
            'Menciones\Controller\Programas'       => 'Menciones\Controller\ProgramasController',
            
            'Menciones\Controller\Tablapautas'     => 'Menciones\Controller\TablapautasController',
            'Menciones\Controller\Tablaclientes'   => 'Menciones\Controller\TablaclientesController',
            'Menciones\Controller\Tablacampanas'   => 'Menciones\Controller\TablacampanasController',
            'Menciones\Controller\Tablamenciones'  => 'Menciones\Controller\TablamencionesController',
            'Menciones\Controller\Tablalocutor'    => 'Menciones\Controller\TablalocutorController',
            'Menciones\Controller\Tablacategorias' => 'Menciones\Controller\TablacategoriasController',
            'Menciones\Controller\Tablaprogramas'  => 'Menciones\Controller\TablaprogramasController',
        ),
    ),
    'router' => array(
        'routes' => array(

            //==============================================
            //CLIENTES CONTROLLER
            'clientes' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/clientes',
                    'defaults' => array(
                        'controller' => 'Menciones\Controller\Clientes',
                        'action'     => 'clientes',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'clientesIngresar' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/ingresar',
                            'defaults' => array(
                                'action'        => 'ingresar',
                            ),
                        ),
                    ),
                    'clientesEditar' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/editar',
                            'defaults' => array(
                                'action'        => 'editar',
                            ),
                        ),
                    ),
                    'clientesGetData' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/get-data',
                            'defaults' => array(
                                'action'        => 'getdatos',
                            ),
                        ),
                    ),
                    'clientesEliminar' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/eliminar',
                            'defaults' => array(
                                'action'        => 'eliminar',
                            ),
                        ),
                    ),
                    'clientesAutorcomplete' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/autorcomplete',
                            'defaults' => array(
                                'action'        => 'autocomplete',
                            ),
                        ),
                    ),
                    'clientesExportar' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/exportar',
                            'defaults' => array(
                                'action'        => 'exportar',
                            ),
                        ),
                    ),
                    'clientesTabla' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/tabla',
                            'defaults' => array(
                                'controller'    => 'Menciones\Controller\Tablaclientes',
                                'action'        => 'lista',
                            ),
                        ),
                    )
                ),
            ),        

            //==============================================
            //CAMPANÑAS CONTROLLER
            'campanas' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/campanas',
                    'defaults' => array(
                        'controller' => 'Menciones\Controller\Campanas',
                        'action'     => 'campanas',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'campanasDetalle' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/detalle[/:id]',
                            'defaults' => array(
                                'action'        => 'detalle',
                            ),
                            'constraints' => array(
                                'id' => '[0-9]+',
                            ),
                        ),
                    ),
                    'campanasIngresar' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/ingresar',
                            'defaults' => array(
                                'action'        => 'ingresar',
                            ),
                        ),
                    ),
                    'campanasEditar' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/editar',
                            'defaults' => array(
                                'action'        => 'editar',
                            ),
                        ),
                    ),
                    'campanasGetData' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/get-data',
                            'defaults' => array(
                                'action'        => 'getdatos',
                            ),
                        ),
                    ),
                    'campanasEliminar' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/eliminar',
                            'defaults' => array(
                                'action'        => 'eliminar',
                            ),
                        ),
                    ),
                    'campanasStatus' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/change-status',
                            'defaults' => array(
                                'action'        => 'changestatus',
                            ),
                        ),
                    ),
                    'campanasAutorcomplete' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/autorcomplete',
                            'defaults' => array(
                                'action'        => 'autocomplete',
                            ),
                        ),
                    ),
                    'campanasTabla' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/tabla',
                            'defaults' => array(
                                'controller'    => 'Menciones\Controller\Tablacampanas',
                                'action'        => 'lista',
                            ),
                        ),
                    )
                ),
            ),
            
            //==============================================
            //MENCIONES CONTROLLER
            'menciones' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/menciones',
                    'defaults' => array(
                        'controller' => 'Menciones\Controller\Menciones',
                        'action'     => 'ingresar',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'mencionesIngresar' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/ingresar',
                            'defaults' => array(
                                'action'        => 'ingresar',
                            ),
                        ),
                    ),
                    'mencionesEditar' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/editar',
                            'defaults' => array(
                                'action'        => 'editar',
                            ),
                        ),
                    ),
                    'mencionesGetData' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/get-data',
                            'defaults' => array(
                                'action'        => 'getdatos',
                            ),
                        ),
                    ),
                    'mencionesEliminar' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/eliminar',
                            'defaults' => array(
                                'action'        => 'eliminar',
                            ),
                        ),
                    ),
                    'mencionesStatus' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/change-status',
                            'defaults' => array(
                                'action'        => 'changestatus',
                            ),
                        ),
                    ),
                    'mencionesAutorcomplete' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/autorcomplete',
                            'defaults' => array(
                                'action'        => 'autocomplete',
                            ),
                        ),
                    ),
                    'mencionesTabla' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/tabla',
                            'defaults' => array(
                                'controller'    => 'Menciones\Controller\Tablamenciones',
                                'action'        => 'lista',
                            ),
                        ),
                    ),
                    'mencionesCalendario' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/calendario',
                            'defaults' => array(
                                'controller'    => 'Menciones\Controller\Tablamenciones',
                                'action'        => 'calendario',
                            ),
                        ),
                    ),
                    'mencionesStatusRead' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/leida',
                            'defaults' => array(
                                'controller'    => 'Menciones\Controller\Locutor',
                                'action'        => 'leida',
                            ),
                        ),
                    )
                ),
            ),
            
            //==============================================
            //HORARIO CONTROLLER
            'horarios' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/horarios',
                    'defaults' => array(
                        'controller' => 'Menciones\Controller\Horarios',
                        'action'     => 'horarios',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'horariosEliminar' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/eliminar',
                            'defaults' => array(
                                'action'        => 'eliminar',
                            ),
                        ),
                    ),
                ),
            ),

            //==============================================
            //PAUTAS CONTROLLER
            'pautas' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/pautas',
                    'defaults' => array(
                        'controller' => 'Menciones\Controller\Pautas',
                        'action'     => 'pautas',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'pautasIngresar' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/ingresar',
                            'defaults' => array(
                                'action'        => 'ingresar',
                            ),
                        ),
                    ),
                    'pautasEditar' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/editar',
                            'defaults' => array(
                                'action'        => 'editar',
                            ),
                        ),
                    ),
                    'pautasGetData' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/get-data',
                            'defaults' => array(
                                'action'        => 'getdatos',
                            ),
                        ),
                    ),
                    'pautasEliminar' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/eliminar',
                            'defaults' => array(
                                'action'        => 'eliminar',
                            ),
                        ),
                    ),
                    'pautasFiltro' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/filtro',
                            'defaults' => array(
                                'action'        => 'filtro',
                            ),
                        ),
                    ),
                    'pautasTabla' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/tabla',
                            'defaults' => array(
                                'controller'    => 'Menciones\Controller\Tablapautas',
                                'action'        => 'lista',
                            ),
                        ),
                    ),
                    'pautasStatusRead' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/leida',
                            'defaults' => array(
                                'controller'    => 'Menciones\Controller\Locutor',
                                'action'        => 'pautaleida',
                            ),
                        ),
                    )
                ),
            ),

            //==============================================
            //LOCUTOR CONTROLLER
            'locutor' => array(
                'type' => 'Literal',
                'options' => array(
                    'route'    => '/locutor',
                    'defaults' => array(
                        'controller' => 'Menciones\Controller\Locutor',
                        'action'     => 'locutor',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'locutorNotificaciones' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/notificaciones',
                            'defaults' => array(
                                'action'        => 'notifications',
                            ),
                        ),
                    ),
                    'locutorTabla' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/tabla',
                            'defaults' => array(
                                'controller'    => 'Menciones\Controller\Tablalocutor',
                                'action'        => 'lista',
                            ),
                        ),
                    ),
                    'locutorTablaPautas' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/tabla-pautas',
                            'defaults' => array(
                                'controller'    => 'Menciones\Controller\Tablalocutor',
                                'action'        => 'listapautas',
                            ),
                        ),
                    ),
                ),
            ),

            //==============================================
            //CATEGORÍAS CONTROLLER
            'categorias' => array(
                'type'   => 'Literal',
                'options' => array(
                    'route'    => 'categorias',
                    'defaults' => array(
                        'controller' => 'Menciones\Controller\Categorias',
                        'action'     => 'categorias',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'categoriasIngresar' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/ingresar',
                            'defaults' => array(
                                'action'        => 'ingresar',
                            ),
                        ),
                    ),
                    'categoriasEditar' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/editar',
                            'defaults' => array(
                                'action'        => 'editar',
                            ),
                        ),
                    ),
                    'categoriasGetData' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/get-data',
                            'defaults' => array(
                                'action'        => 'getdatos',
                            ),
                        ),
                    ),
                    'categoriasEliminar' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/eliminar',
                            'defaults' => array(
                                'action'        => 'eliminar',
                            ),
                        ),
                    ),
                    'categoriasAutorcomplete' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/autorcomplete',
                            'defaults' => array(
                                'action'        => 'autocomplete',
                            ),
                        ),
                    ),
                    'categoriasExportar' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/exportar',
                            'defaults' => array(
                                'action'        => 'exportar',
                            ),
                        ),
                    ),
                    'categoriasTabla' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/tabla',
                            'defaults' => array(
                                'controller'    => 'Menciones\Controller\Tablacategorias',
                                'action'        => 'lista',
                            ),
                        ),
                    )
                ),
            ),

            //==============================================
            //PROGRAMAS CONTROLLER
            'programas' => array(
                'type'   => 'Literal',
                'options' => array(
                    'route'    => 'programas',
                    'defaults' => array(
                        'controller' => 'Menciones\Controller\Programas',
                        'action'     => 'programas',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'programasIngresar' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/ingresar',
                            'defaults' => array(
                                'action'        => 'ingresar',
                            ),
                        ),
                    ),
                    'programasEditar' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/editar',
                            'defaults' => array(
                                'action'        => 'editar',
                            ),
                        ),
                    ),
                    'programasGetData' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/get-data',
                            'defaults' => array(
                                'action'        => 'getdatos',
                            ),
                        ),
                    ),
                    'programasEliminar' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/eliminar',
                            'defaults' => array(
                                'action'        => 'eliminar',
                            ),
                        ),
                    ),
                    'programasAutorcomplete' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/autorcomplete',
                            'defaults' => array(
                                'action'        => 'autocomplete',
                            ),
                        ),
                    ),
                    'programasExportar' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/exportar',
                            'defaults' => array(
                                'action'        => 'exportar',
                            ),
                        ),
                    ),
                    'programasTabla' => array(
                        'type'    => 'Literal',
                        'options' => array(
                            'route'    => '/tabla',
                            'defaults' => array(
                                'controller'    => 'Menciones\Controller\Tablaprogramas',
                                'action'        => 'lista',
                            ),
                        ),
                    )
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => array(
            'layout/layout'                         => __DIR__ . '/../view/layout/layout.phtml',
            'menciones/menciones/menciones'         => __DIR__ . '/../view/menciones/menciones/menciones.phtml',
            'error/404'                             => __DIR__ . '/../view/error/404.phtml',
            'error/index'                           => __DIR__ . '/../view/error/index.phtml',
        ),
        'template_path_stack' => array(
            'Menciones' => __DIR__ . '/../view',
        ),
        'strategies'=>array(
            'ViewJsonStrategy'
        ),
    ),
    'module_layouts' => array(
        'Menciones' => array(
            'default' => 'layout/layout',
        )
    ),
);
