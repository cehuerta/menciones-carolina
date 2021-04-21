<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Authentication\Storage\Session as SessionStorage;

class Module
{
    // public function onBootstrap(MvcEvent $e)
    // {
    //     $eventManager        = $e->getApplication()->getEventManager();
    //     $moduleRouteListener = new ModuleRouteListener();
    //     $moduleRouteListener->attach($eventManager);
    // }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }


    // Este método se ejecuta cada vez que carga una página
    public function onBootstrap(MvcEvent $e){
        //Iniciamos la lista de control de acceso
        $this->initAcl($e);
         
        //Comprobamos la lista de control de acceso
        $e->getApplication()->getEventManager()->attach('route', array($this, 'checkAcl'));
    }

    public function initAcl(MvcEvent $e){
        //Creamos el objeto ACL
        $acl = new \Zend\Permissions\Acl\Acl();
         
        //Incluimos la lista de roles y permisos, nos devuelve un array
        $roles=require_once 'config/autoload/acl.roles.php';
         
        foreach($roles as $role => $resources){
           
            //Indicamos que el rol será genérico
            $role = new \Zend\Permissions\Acl\Role\GenericRole($role);
             
            //Añadimos el rol al ACL
            $acl->addRole($role);
             
            //Recorremos los recursos o rutas permitidas
            foreach($resources["allow"] as $resource){
             
                //Si el recurso no existe lo añadimos
                 if(!$acl->hasResource($resource)){
                    $acl->addResource(new \Zend\Permissions\Acl\Resource\GenericResource($resource));
                 }
                  
                 //Permitimos a ese rol ese recurso
                 $acl->allow($role, $resource);
            }
             
            foreach ($resources["deny"] as $resource) {
                  
                 //Si el recurso no existe lo añadimos
                 if(!$acl->hasResource($resource)){
                    $acl->addResource(new \Zend\Permissions\Acl\Resource\GenericResource($resource));
                 }
                  
                 //Denegamos a ese rol ese recurso
                 $acl->deny($role, $resource);
            }
  
        }
         
        //Establecemos la lista de control de acceso
        $e->getViewModel()->acl=$acl;
    }

    public function checkAcl(MvcEvent $e){

        //guardamos el nombre de la ruta o recurso a permitir o denegar
        $route=$e->getRouteMatch()->getMatchedRouteName();
         
        //Instanciamos el servicio de autenticacion
        $auth=new \Zend\Authentication\AuthenticationService();
        $auth->setStorage(new SessionStorage('SistemaDeMenciones_17012017120000_hDfDD5f558s52dD_SDjk_DbySD_sdFdgGg4drdaAdsEtgb6yhn'));
        $identi=$auth->getStorage()->read();
         
         
        // Establecemos nuestro rol
        // Si el usuario esta identificado le asignaremos el rol segun el tipo de user y si no el rol visitante.
        if($identi!=false && $identi!=null){
            if ($identi->tipo_user==1 or $identi->tipo_user==2) {
                $userRole="administrador";//super admin; admin
            }
            elseif ($identi->tipo_user==3) {
                $userRole="locutor";
            }else{
                $userRole="visitante";
            }

        }else{
           $userRole="visitante";
        }
         
        //Comprobamos si no está permitido para ese rol esa ruta
        if(!$e->getViewModel()->acl->isAllowed($userRole, $route)) {
            //Devolvemos un error 404
            $response = $e->getResponse();
            $response->getHeaders()->addHeaderLine('Location', $e->getRequest()->getBaseUrl() . '/404');
            $response->setStatusCode(404);
        }
         
    }

}
