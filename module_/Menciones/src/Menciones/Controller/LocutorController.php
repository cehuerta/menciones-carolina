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
use Menciones\Model\Entity\Modelomencionesvistas;


use Application\Model\Entity\Modelofunctions;
use Application\Model\Entity\Modelodashboard;

//******************************************
use Zend\Http\Client;
use Zend\Http\Client\Adapter\Curl;
//******************************************

class LocutorController extends AbstractActionController
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


    public function locutorAction()
    {
        $identidad=$this->validaSesion();
        if( $identidad==false ){
            return $this->redirect()->toRoute( 'login' );
        }
        if( $identidad->tipo_user!=3 ){
            $this->flashMessenger()->setNamespace("msjError")->addMessage("No tienes permitida esta opción.");
            return $this->redirect()->toRoute( 'login' );
        }
        $this->dbAdapter    = $this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloFunctions    = new Modelofunctions($this->dbAdapter);
        $modeloCampana      = new Modelocampanas($this->dbAdapter);
        $modeloMencionVista = new Modelomencionesvistas($this->dbAdapter);
        $modeloDashboard    = new Modelodashboard($this->dbAdapter);


        $dia            = date('Y-m-d');
        $dia_num        = date('N');
        $t_leidas_dia   = $modeloMencionVista->contCustom(null, null, null, $dia, 1 );
        $t_menciones_dia= $modeloDashboard->totalMenciones($dia, $dia_num, 1 );
        $t_no_leidas_dia= (int)$t_menciones_dia-$t_leidas_dia;
        return new ViewModel(array(
            'NoImage'           => $this->rutas["NoImage"],
            't_menciones_dia'   => $t_menciones_dia,
            't_leidas_dia'      => $t_leidas_dia,
            't_no_leidas_dia'   => $t_no_leidas_dia
        )); 
    }

    public function leidaAction()
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

        if( $identidad->tipo_user!=3 ){
            $json           =array();
            $json["status"] ="error";
            $json["msj"]    ="No tienes permitida esta opción.";
            return new JsonModel($json);
        }

        $this->dbAdapter    = $this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloFunctions    = new Modelofunctions($this->dbAdapter);
        $modeloMencion      = new Modelomenciones($this->dbAdapter);
        $modeloCampana      = new Modelocampanas($this->dbAdapter);
        $modeloHorario      = new Modelohorarios($this->dbAdapter);
        $modeloMencionVista = new Modelomencionesvistas($this->dbAdapter);
        $modeloDashboard    = new Modelodashboard($this->dbAdapter);
        
        $json           =array();
        $id_user        = $identidad->id_user;
        $dia            = date('Y-m-d');
        $dia_num        = date('N');
        $t_leidas_dia   =0;
        $t_menciones_dia=0;

        $idsched=0;
        if( !isset($_POST['horario']) or !is_numeric($_POST['horario']) or abs($_POST['horario'])<=0 ){
            $json["status"]="ok";
            $json["msj"]="El horario no existe.";
            return new JsonModel($json);
        }
        if( !isset($_POST['dia']) and empty($_POST['dia']) and strlen($_POST['dia'])>10 ){
            $json["status"]="ok";
            $json["msj"]="El día no esta declarado.";
            return new JsonModel($json);
        }
        $idsched=abs($_POST['horario']);
        $dia    =addslashes($_POST['dia']);

        try {
            $horario = $modeloHorario->get($idsched);
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            $json["msj_e"]=(string)$e;
            return new JsonModel($json);
        }
        if($horario==null){
            $json["status"]="error";
            $json["msj"]="El horario no existe.";
            return new JsonModel($json);
        }

        try {
            $mencion = $modeloMencion->get($horario['idm']);
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            $json["msj_e"]=(string)$e;
            return new JsonModel($json);
        }
        if($mencion==null){
            $json["status"]="error";
            $json["msj"]="La mención no existe.";
            return new JsonModel($json);
        }

        try {
            $campana = $modeloCampana->get($mencion['idcamp']);
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            $json["msj_e"]=(string)$e;
            return new JsonModel($json);
        }
        if($campana==null){
            $json["status"]="error";
            $json["msj"]="La campaña no existe.";
            return new JsonModel($json);
        }

        try {
            $mencion_leida  = $modeloMencionVista->contCustom($mencion['idm'], $horario['idsched'], null, $dia );
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            $json["msj_e"]=(string)$e;
            return new JsonModel($json);
        }

        if( (int)$mencion_leida>0){
            $json["status"]="error";
            $json["msj"]="La mención ya fue leida previamente.";
            return new JsonModel($json);
        }

        $datos                              =array();
        $datos["idm"]                       =$mencion['idm'];
        $datos["idsched"]                   =$horario['idsched'];
        $datos["id_user"]                   =$id_user;
        $datos["mention_view_date"]         =$dia;
        $datos["mention_view_date_create"]  =new Expression('now()');
        

        //INGRESAMOS
        try {
            $ultimo=$modeloMencionVista->add($datos);
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            $json["msj_e"]=(string)$e;
            return new JsonModel($json);
        }

        //CONSULTAMOS
        // try {
        //     $last_mention_view=$modeloMencionVista->get($ultimo);
        // } catch (\Exception $e) {
        //     $json["status"]="error";
        //     $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
        //     $json["msj_e"]=(string)$e;
        //     return new JsonModel($json);
        // }

        //***********************************************************************************
        //OBTENEMOS LOS CONTADORES ACTUALIZADOS
        try {
            $t_leidas_dia   = $modeloMencionVista->contCustom(null, null, $id_user, $dia );
        } catch (\Exception $e) {}

         try {
            $t_menciones_dia= $modeloDashboard->totalMenciones($dia, $dia_num, 1 );
        } catch (\Exception $e) {}

        $t_no_leidas_dia= (int)$t_menciones_dia-$t_leidas_dia;
        //***********************************************************************************

        // strtotime($last_mention_view['mention_view_date_create'])
        try {
            $postParams                        =array();
            $postParams["epoch_mention"]       =time();
            $postParams["title_mention"]       =stripslashes($campana['campaign_title']).' | '.$horario['schedule_hour_f'];
            $postParams["description_mention"] =$mencion['mention_description'];
            $postParams["hash_private"]        =$this->rutas['api_rudo']['hash_private'];

            $config = new Curl();
            $client = new Client($this->rutas['api_rudo']['urls']['send_mention']);
            $client->setAdapter($config);
            $client->setMethod('POST');
            $client->setParameterPost( $postParams );
            $response = $client->send($client->getRequest());

            // OUTPUT THE RESPONSE
            $resp=$response->getBody();
            $resultado_api=json_decode($resp, true);

            $msg_rudo_mencion = "Momento marcado en Rudo";
            if($resultado_api==null or $resultado_api==false or $resultado_api["status"]=='error'){
                $msg_rudo_mencion = "No fue posible marcar el momento en Rudo";
                $msj_error        = "No fue posible marcar el momento en Rudo";
                if(isset($resultado_api["msj"])){
                    $msj_error  =$resultado_api["msj"];
                }
            }
        } catch (\Exception $e) {
            $msg_rudo_mencion = "No fue posible marcar el momento en Rudo";
        }
        

        $json["status"]="ok";
        $json["msj"]="Mencion marcada como leida. (".$msg_rudo_mencion.')';
        $json['contadores']=array(
            't_menciones_dia'   => $t_menciones_dia,
            't_leidas_dia'      => $t_leidas_dia,
            't_no_leidas_dia'   => $t_no_leidas_dia
        );
        return new JsonModel($json);
    }



}
