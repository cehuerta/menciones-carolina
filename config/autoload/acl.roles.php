<?php
/*
     ADMINISTRADOR: CONSTITUYE AL SUPERADMINISTRADOR Y ADMINISTRADOR DEL SISTEMA (VALIDADO SOLO EN DETERMINADOS PUNTOS DENTRO DEL SISTEMA).
     CLIENTE: USUARIO TIPO CLIENTE.
*/
return array(
     'administrador'=> array(
          "allow"=>array(
               'login',                                     //->INICIO INDEX CONTROLLER
               'login2',
               'home',
               'cerrar',                                    //.-FIN INDEX CONTROLLER

               'dashboard',                                 //->INICIO DASHBOARD CONTROLLER (SOLO PARA LOS ADMINISTRADORES)
               'dashboard/dashboardConectados',             //.-FIN DASHBOARD CONTROLLER (SOLO PARA LOS ADMINISTRADORES)

               'clientes',                                  //->INICIO CLIENTES CONTROLLER
               'clientes/clientesIngresar',
               'clientes/clientesEditar',
               'clientes/clientesGetData',
               'clientes/clientesEliminar',
               'clientes/clientesAutorcomplete',
               'clientes/clientesExportar',
               'clientes/clientesTabla',                    //.-FIN CLIENTES CONTROLLER

               'campanas',                                  //->INICIO CAMPAÑAS CONTROLLER
               'campanas/campanasDetalle',
               'campanas/campanasIngresar',
               'campanas/campanasEditar',
               'campanas/campanasGetData',
               'campanas/campanasEliminar',
               'campanas/campanasStatus',
               'campanas/campanasAutorcomplete',
               'campanas/campanasTabla',                    //.-FIN CAMPAÑAS CONTROLLER

               'menciones',                                 //->INICIO MENCIONES CONTROLLER
               'menciones/mencionesIngresar',
               'menciones/mencionesEditar',
               'menciones/mencionesGetData',
               'menciones/mencionesEliminar',
               'menciones/mencionesStatus',
               'menciones/mencionesAutorcomplete',
               'menciones/mencionesTabla',
               'menciones/mencionesCalendario',
               'menciones/mencionesStatusRead',             //.-FIN MENCIONES CONTROLLER

               'horarios',                                  //->INICIO HORARIOS CONTROLLER
               'horarios/horariosEliminar',                 //.-FIN HORARIOS CONTROLLER

               'locutor',                                   //->INICIO LOCUTOR CONTROLLER
               'locutor/locutorNotificaciones',
               'locutor/locutorTablaPautas',
               'locutor/locutorTabla',                      //.-FIN LOCUTOR CONTROLLER

               'adms',                                      //->INICIO USUARIO CONTROLLER
               'ingresaradm',
               'saveajaxusuario',
               'editaradm',
               'saveeditarajax',
               'usuarioChangeStatus',
               'eliminaradm',
               'viewsendmail',
               'editarmisdatos',
               'editarMisdatosAjax',
               'tablausuarios',                             //.-FIN USUARIO CONTROLLER


               'pautas',                                  //->INICIO PAUTAS CONTROLLER
               'pautas/pautasIngresar',
               'pautas/pautasEditar',
               'pautas/pautasGetData',
               'pautas/pautasEliminar',
               'pautas/pautasFiltro',
               'pautas/pautasStatusRead',
               'pautas/pautasTabla',                    //.-FIN PAUTAS CONTROLLER

               'categorias',                              //->INICIO CATEGORÍAS CONTROLLER
               'categorias/categoriasIngresar',
               'categorias/categoriasEditar',
               'categorias/categoriasGetData',
               'categorias/categoriasEliminar',
               'categorias/categoriasAutorcomplete',
               'categorias/categoriasExportar',
               'categorias/categoriasTabla',                //.-FIN CATEGORÍAS CONTROLLER

               'programas',                              //->INICIO PROGRAMAS CONTROLLER
               'programas/programasIngresar',
               'programas/programasEditar',
               'programas/programasGetData',
               'programas/programasEliminar',
               'programas/programasAutorcomplete',
               'programas/programasExportar',
               'programas/programasTabla',                //.-FIN PROGRAMAS CONTROLLER

          ),
          "deny"=>array(
               'recuperar',                                 //->INICIO RECUPERAR CONTROLLER
               'codigo',                                    //.-FIN RECUPERAR CONTROLLER
          )
    ),
    'locutor'=> array(
          "allow"=>array(
               'login',                                     //->INICIO INDEX CONTROLLER
               'login2',
               'home',
               'cerrar',                                    //.-FIN INDEX CONTROLLER

               'dashboard',                                 //->INICIO DASHBOARD CONTROLLER (SOLO PARA LOS ADMINISTRADORES)
               'dashboard/dashboardConectados',             //.-FIN DASHBOARD CONTROLLER (SOLO PARA LOS ADMINISTRADORES)

               'menciones/mencionesGetData',                //->INICIO MENCIONES CONTROLLER
               'menciones/mencionesTabla',
               'menciones/mencionesCalendario',
               'menciones/mencionesStatusRead',             //.-FIN MENCIONES CONTROLLER

               'locutor',                                   //->INICIO LOCUTOR CONTROLLER
               'locutor/locutorNotificaciones',
               'locutor/locutorTablaPautas',
               'locutor/locutorTabla',                      //.-FIN LOCUTOR CONTROLLER

               'editarmisdatos',                            //->INICIO USUARIO CONTROLLER
               'editarMisdatosAjax',                        //.-FIN USUARIO CONTROLLER


               'pautas/pautasGetData',                      //->INICIO PAUTAS CONTROLLER 
               'pautas/pautasStatusRead',                   //.-FIN PAUTAS CONTROLLER

          ),
          "deny"=>array(
               'recuperar',                                 //->INICIO RECUPERAR CONTROLLER
               'codigo',                                    //.-FIN RECUPERAR CONTROLLER

               'clientes',                                  //->INICIO CLIENTES CONTROLLER
               'clientes/clientesIngresar',
               'clientes/clientesEditar',
               'clientes/clientesGetData',
               'clientes/clientesEliminar',
               'clientes/clientesAutorcomplete',
               'clientes/clientesExportar',
               'clientes/clientesTabla',                    //.-FIN CLIENTES CONTROLLER

               'campanas',                                  //->INICIO CAMPAÑAS CONTROLLER
               'campanas/campanasDetalle',
               'campanas/campanasIngresar',
               'campanas/campanasEditar',
               'campanas/campanasGetData',
               'campanas/campanasEliminar',
               'campanas/campanasStatus',
               'campanas/campanasAutorcomplete',
               'campanas/campanasTabla',                    //.-FIN CAMPAÑAS CONTROLLER

               'menciones',                                 //->INICIO MENCIONES CONTROLLER
               'menciones/mencionesIngresar',
               'menciones/mencionesEditar',
               'menciones/mencionesEliminar',
               'menciones/mencionesStatus',
               'menciones/mencionesAutorcomplete',          //.-FIN MENCIONES CONTROLLER

               'horarios',                                  //->INICIO HORARIOS CONTROLLER
               'horarios/horariosEliminar',                 //.-FIN HORARIOS CONTROLLER

               'adms',                                      //->INICIO USUARIO CONTROLLER
               'ingresaradm',
               'saveajaxusuario',
               'editaradm',
               'saveeditarajax',
               'usuarioChangeStatus',
               'eliminaradm',
               'viewsendmail',
               'tablausuarios',                             //.-FIN USUARIO CONTROLLER


               'pautas',                                  //->INICIO PAUTAS CONTROLLER
               'pautas/pautasIngresar',
               'pautas/pautasEditar',
               'pautas/pautasEliminar',
               'pautas/pautasFiltro',
               'pautas/pautasTabla',                    //.-FIN PAUTAS CONTROLLER

               'categorias',                              //->INICIO CATEGORÍAS CONTROLLER
               'categorias/categoriasIngresar',
               'categorias/categoriasEditar',
               'categorias/categoriasGetData',
               'categorias/categoriasEliminar',
               'categorias/categoriasAutorcomplete',
               'categorias/categoriasExportar',
               'categorias/categoriasTabla',                //.-FIN CATEGORÍAS CONTROLLER

               'programas',                              //->INICIO PROGRAMAS CONTROLLER
               'programas/programasIngresar',
               'programas/programasEditar',
               'programas/programasGetData',
               'programas/programasEliminar',
               'programas/programasAutorcomplete',
               'programas/programasExportar',
               'programas/programasTabla',                //.-FIN PROGRAMAS CONTROLLER
          )
    ),
    'visitante'=> array(
          "allow"=>array(
               'login',                                     //->INICIO INDEX CONTROLLER
               'login2',
               'home',
               'cerrar',                                    //.-FIN INDEX CONTROLLER 
               'recuperar',                                 //->INICIO RECUPERAR CONTROLLER
               'codigo',                                    //.-FIN RECUPERAR CONTROLLER
          ),
          "deny"=>array(
               'dashboard',                                 //->INICIO DASHBOARD CONTROLLER (SOLO PARA LOS ADMINISTRADORES)
               'dashboard/dashboardConectados',             //.-FIN DASHBOARD CONTROLLER (SOLO PARA LOS ADMINISTRADORES)

               'clientes',                                  //->INICIO CLIENTES CONTROLLER
               'clientes/clientesIngresar',
               'clientes/clientesEditar',
               'clientes/clientesGetData',
               'clientes/clientesEliminar',
               'clientes/clientesAutorcomplete',
               'clientes/clientesExportar',
               'clientes/clientesTabla',                    //.-FIN CLIENTES CONTROLLER

               'campanas',                                  //->INICIO CAMPAÑAS CONTROLLER
               'campanas/campanasDetalle',
               'campanas/campanasIngresar',
               'campanas/campanasEditar',
               'campanas/campanasGetData',
               'campanas/campanasEliminar',
               'campanas/campanasStatus',
               'campanas/campanasAutorcomplete',
               'campanas/campanasTabla',                    //.-FIN CAMPAÑAS CONTROLLER

               'menciones',                                 //->INICIO MENCIONES CONTROLLER
               'menciones/mencionesIngresar',
               'menciones/mencionesEditar',
               'menciones/mencionesGetData',
               'menciones/mencionesEliminar',
               'menciones/mencionesStatus',
               'menciones/mencionesAutorcomplete',
               'menciones/mencionesTabla',
               'menciones/mencionesCalendario',
               'menciones/mencionesStatusRead',             //.-FIN MENCIONES CONTROLLER
               
               'horarios',                                  //->INICIO HORARIOS CONTROLLER
               'horarios/horariosEliminar',                 //.-FIN HORARIOS CONTROLLER

               'locutor',                                   //->INICIO LOCUTOR CONTROLLER
               'locutor/locutorNotificaciones',
               'locutor/locutorTablaPautas',
               'locutor/locutorTabla',                      //.-FIN LOCUTOR CONTROLLER

               'adms',                                      //->INICIO USUARIO CONTROLLER
               'ingresaradm',
               'saveajaxusuario',
               'editaradm',
               'saveeditarajax',
               'usuarioChangeStatus',
               'eliminaradm',
               'viewsendmail',
               'editarmisdatos',
               'editarMisdatosAjax',
               'tablausuarios',                             //.-FIN USUARIO CONTROLLER


               'pautas',                                  //->INICIO PAUTAS CONTROLLER
               'pautas/pautasIngresar',
               'pautas/pautasEditar',
               'pautas/pautasGetData',
               'pautas/pautasEliminar',
               'pautas/pautasFiltro',
               'pautas/pautasStatusRead',
               'pautas/pautasTabla',                    //.-FIN PAUTAS CONTROLLER

               'categorias',                              //->INICIO CATEGORÍAS CONTROLLER
               'categorias/categoriasIngresar',
               'categorias/categoriasEditar',
               'categorias/categoriasGetData',
               'categorias/categoriasEliminar',
               'categorias/categoriasAutorcomplete',
               'categorias/categoriasExportar',
               'categorias/categoriasTabla',                //.-FIN CATEGORÍAS CONTROLLER

               'programas',                              //->INICIO PROGRAMAS CONTROLLER
               'programas/programasIngresar',
               'programas/programasEditar',
               'programas/programasGetData',
               'programas/programasEliminar',
               'programas/programasAutorcomplete',
               'programas/programasExportar',
               'programas/programasTabla',                //.-FIN PROGRAMAS CONTROLLER
          )
    ),
);
?>