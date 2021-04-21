<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonModule for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Menciones\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

use Zend\Validator;
use Zend\Filter;
use Zend\I18n\Validator as I18nValidator;
use Zend\Db\Adapter\Adapter;
use Zend\Crypt\Password\Bcrypt;
use Zend\Authentication\Adapter\DbTable as AuthAdapter;

use Zend\Db\Sql\Predicate\Expression;

use Zend\View\Renderer\PhpRenderer as PhpRenderer;
use Zend\View\Resolver\TemplateMapResolver as TemplateMapResolver;

//Componentes de autenticación
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\Session as SessionStorage;
use Zend\Session\Container;


// modelos
use Menciones\Model\Entity\Modelomenciones;
use Menciones\Model\Entity\Modelocampanas;
use Menciones\Model\Entity\Modelohorarios;


use Application\Model\Entity\Modelofunctions;

class HorariosController extends AbstractActionController
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



    public function horariosAction()
    {
        $identidad=$this->validaSesion();
        // NADA AQUI
        return $this->redirect()->toRoute( 'login' );
    }

    public function eliminarAction()
    {
        $identidad=$this->validaSesion();
        if( $identidad==false and !$this->request->isXmlHttpRequest() ){
            return $this->redirect()->toRoute( 'login' );
        }
        else if( $identidad==false and $this->request->isXmlHttpRequest() ){
            $json           =array();
            $json["status"] ="error";
            $json["msj"]    ="Sesión caducada, por favor ingrese nuevamente.";
            return new JsonModel($json);
        }
        else if( !$this->request->isXmlHttpRequest()){
            return $this->redirect()->toRoute( 'login' );
        }

        $this->dbAdapter    =$this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloHorario      =new Modelohorarios($this->dbAdapter);
        $modeloFunctions    =new Modelofunctions($this->dbAdapter);


        $json       =array();
        if (!isset($_POST["horario"]) ) {
            $json["status"]="error";
            $json["msj"]="Debe seleccionar un horario válido.";
            return new JsonModel($json);
        }
        try {
            $idsched    = abs($_POST["horario"]);
            $horario    = $modeloHorario->get($idsched);
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            return new JsonModel($json);
        }

        if ( $horario==NULL ) {
            $json["status"]="error";
            $json["msj"]="Debe seleccionar un horario válido.";
            return new JsonModel($json);
        }

        try {
            $modeloHorario->delete( $horario["idsched"] );
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            return new JsonModel($json);
        }
        
        

        $json["status"]="ok";
        $json["msj"]="Horario eliminado correctamente.";
        return new JsonModel($json);
    }

}
