<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;


use Zend\Validator;
use Zend\I18n\Validator as I18nValidator;
use Zend\Db\Adapter\Adapter;
use Zend\Crypt\Password\Bcrypt;
use Zend\Authentication\Adapter\DbTable as AuthAdapter;

//Componentes de autenticación
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\Session as SessionStorage;
use Zend\Session\Container;

use Usuario\Model\Entity\Modelopersonal;


class IndexController extends AbstractActionController
{
	public $dbAdapter;
    private $auth;
    public $rutas;

    public function __construct() {
        $this->auth = new AuthenticationService();
        $this->auth->setStorage(new SessionStorage('SistemaDeMenciones_17012017120000_hDfDD5f558s52dD_SDjk_DbySD_sdFdgGg4drdaAdsEtgb6yhn'));
    }

	public function validaSesion(){

        $identi=$this->auth->getStorage()->read();

        if($identi!=false && $identi!=null){
            
            // -----------------------------------------------------------------------
            // declaracion de rutas
            $this->rutas=$this->getServiceLocator()->get('Config');

            if( !empty($identi->logo) ) {
                $this->layout()->logo = $this->rutas["rutas"]["rutaUser"]."/".$identi->slug."/".$identi->logo;
            }
            else{
                $this->layout()->logo = $this->getRequest()->getBaseUrl().'/public/img/'.$this->rutas["NoImage"];
            }
            $this->layout()->nameUser = $identi->nombre_completo;
            $this->layout()->type = $identi->tipo_user;

        }else{
            return false;
        }

        return $identi;
    }



    public function indexAction()
    {
        return new ViewModel();
    }

    public function loginAction()
    {
        
        $layout = $this->layout();
        $layout->setTemplate('layout/layoutLogin.phtml');

        $auth = $this->auth;
        $identi=$auth->getStorage()->read();

        if($identi!=false && $identi!=null){
            return $this->redirect()->toRoute('home');
        }     

        if($this->getRequest()->isPost()){
         	
         	$this->dbAdapter=$this->getServiceLocator()->get('Zend\Db\Adapter');
            try {

                $authAdapter = new AuthAdapter($this->dbAdapter,
                                           'usuarios',
                                           'correo', 
                                           'password'
                                           );                                   
 
                $authAdapter->setIdentity($this->getRequest()->getPost("correoUser"))
                        ->setCredential(hash("ripemd160",$this->getRequest()->getPost("passwordUser")));

                $auth->setAdapter($authAdapter);
                $result=$auth->authenticate();

            } catch (\Exception $e) {
                $this->flashMessenger()->setNamespace("msjError")->addMessage("Por el momento no es posible procesar su solicitud, por favor intentelo de nuevo mas tarde.");
                return $this->redirect()->toRoute( 'login' );
            }
            
            try {

                if($authAdapter->getResultRowObject()==false){

                    $this->flashMessenger()->setNamespace("msjError")->addMessage("Usuario y/o Contraseña incorrectas, inténtelo nuevamente.");
                    return $this->redirect()->toRoute( 'login' );
                  
                }else{
                    $object_user=$authAdapter->getResultRowObject();
                    if($object_user->status==0){
                        $this->auth->clearIdentity();
                        $this->flashMessenger()->setNamespace("msjError")->addMessage("El usuario se encuentra dado de baja.");
                        return $this->redirect()->toRoute( 'login' );
                    }

                    $auth->getStorage()->write($authAdapter->getResultRowObject());
                    return $this->redirect()->toRoute( 'home' );
                }
            } catch (\Exception $e) {
                $this->flashMessenger()->setNamespace("msjError")->addMessage("Por el momento no es posible procesar su solicitud, por favor intentelo de nuevo mas tarde.");
                return $this->redirect()->toRoute( 'login' );
            }
            
        }     

        
        return new ViewModel(); 
    }

    public function cerrarAction()
    {

        $identi=$this->auth->getStorage()->read();


        $this->auth->clearIdentity();
        return $this->redirect()->toRoute( 'login' );

        return new ViewModel();
    }

    public function homeAction()
    {

    	$identidad=$this->validaSesion();
        if($identidad==false)
            return $this->redirect()->toRoute( 'login' );
        
        if($identidad->tipo_user!=3)
            return $this->redirect()->toRoute( 'dashboard' );
        else
            return $this->redirect()->toRoute( 'dashboard' );
        
    }


}
