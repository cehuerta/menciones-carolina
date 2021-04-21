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
use Menciones\Model\Entity\Modeloclientes;
use Menciones\Model\Entity\Modelomencionesvistas;


use Application\Model\Entity\Modelofunctions;

class MencionesController extends AbstractActionController
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


    public function validaCampos($datos=array(), $action='INGRESAR'){

        if( !isset($datos["mention_description"]) or empty($datos["mention_description"]) or strlen($datos["mention_description"])>60000 ){
            return "Debe ingresar una mención.";
        }

        if( !isset($_POST["mention_days"]) or !is_array($_POST["mention_days"]) or count($_POST["mention_days"])<=0){
            return "Debe ingresar al menos 1 día.";
        }
        else{
            foreach ($_POST["mention_days"] as $key => $value) {
                if( (int)$value<=0 and (int)$value>7 ){
                    return "Debe seleccionar días válidos.";
                }
            }
        }

        if( !isset($datos["mention_date_start"]) or empty($datos["mention_date_start"]) or strlen($datos["mention_date_start"])>10 ){
            return "Debe ingresar una fecha de inicio.";
        }
        if( !isset($datos["mention_date_end"]) or empty($datos["mention_date_end"]) or strlen($datos["mention_date_end"])>10 ){
            return "Debe ingresar una fecha de termino.";
        }

        if( !isset($_POST["mention_schedule"]) or !is_array($_POST["mention_schedule"]) or count($_POST["mention_schedule"])<=0){
            return "Debe ingresar al menos 1 horario.";
        }
        else{
            foreach ($_POST["mention_schedule"] as $key => $value) {
                if( strlen($value)>10 ){
                    return "Debe ingresar horarios válidos.";
                }
            }
            
        }

        return 'ok';
    }

    public function mencionesAction()
    {
        $identidad=$this->validaSesion();
        // NADA AQUI
        return $this->redirect()->toRoute( 'login' );
    }


    //=================================================
    //OBTENER LOS DATOS
    public function getdatosAction()
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

        $this->dbAdapter    = $this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloMencion      = new Modelomenciones($this->dbAdapter);
        $modeloHorario      = new Modelohorarios($this->dbAdapter);
        $modeloFunctions    = new Modelofunctions($this->dbAdapter);
        $modeloCampana      = new Modelocampanas($this->dbAdapter);
        $modeloCliente      = new Modeloclientes($this->dbAdapter);
        $modeloMencionVista = new Modelomencionesvistas($this->dbAdapter);

        

        $json=array();
        if (!isset($_POST["mencion"]) ) {
            $json["status"]="error";
            $json["msj"]="La mención no existe.";
            return new JsonModel($json);
        }

        try {
            $idm=(int)$_POST["mencion"];
            $mencion=$modeloMencion->get($idm);
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            $json["msj_e"]=(string)$e;
            return new JsonModel($json);
        }
        //==============================================================
        //VALIDA
        if ( $mencion==NULL) {
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

        try {
            $cliente = $modeloCliente->get($campana['idc']);
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            $json["msj_e"]=(string)$e;
            return new JsonModel($json);
        }

        $campana_return=array();
        $campana_return['nombre']='';
        if($campana!=null)
            $campana_return['nombre']=stripslashes($campana['campaign_title']);

        $cliente_return=array();
        $cliente_return['nombre']='';
        if($cliente!=null)
            $cliente_return['nombre']=stripslashes($cliente['client_name']);

        try {
            $horarios_tmp=$modeloHorario->all(null, null, $mencion['idm'], null);
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            $json["msj_e"]=(string)$e;
            return new JsonModel($json);
        }

        $horarios=array();
        if(is_array($horarios_tmp) and count($horarios_tmp)>0){
            foreach ($horarios_tmp as $key => $value) {
                $horarios[]=array(
                    'id'    =>$value["idsched"],
                    'hora'  =>$value["schedule_hour_f"],
                );
            }
        }

        $locutor=false;
        if($identidad->tipo_user==3)
            $locutor=true;

        // $t_previas_no_leidas = 0;
        // if($locutor==true && isset($_POST['section']) && (string)$_POST['section']=='tabla_menciones' ):
        //     try {
        //         $hora   = $modeloHorario->get( abs($_POST['hora']) );
        //     } catch (\Exception $e) {
        //         $json["status"]="error";
        //         $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
        //         $json["msj_e"]=(string)$e;
        //         return new JsonModel($json);
        //     }

        //     $dia     = addslashes($_POST['dia']);
        //     $dia_num = date('N', strtotime($dia));

        //     try {
        //         $t_previas_no_leidas = (int)$modeloMencionVista->contUnreadPreview(null, $hora['schedule_hour'], null, $dia, 1, $dia_num );
        //     } catch (\Exception $e) {
        //         $t_previas_no_leidas = 0;
        //     }
            
        //     if($t_previas_no_leidas>0):
        //         $json["status"]="error";
        //         $json["msj"]="Debes marcar como leída la mención anterior, para poder visualizar esta.";
        //         return new JsonModel($json);
        //     endif;
        // endif;

        $json["status"]                 = "ok";
        $json["msj"]                    = "";
        $json["id"]                     = $mencion["idm"];
        $json["mention_description"]    = stripslashes($mencion["mention_description"]);
        $json["mention_description_f"]  = nl2br(stripslashes($mencion["mention_description"]));
        $json["mention_date_start"]     = stripslashes($mencion["mention_date_start"]);
        $json["mention_date_end"]       = stripslashes($mencion["mention_date_end"]);
        $json["mention_date_start_f"]   = stripslashes($mencion["mention_date_start_f"]);
        $json["mention_date_end_f"]     = stripslashes($mencion["mention_date_end_f"]);
        $json["mention_days"]           = explode(',', $mencion["mention_days"]);
        $json["mention_days_name"]      = $modeloFunctions->diasName($mencion["mention_days"]);
        $json["t_mention_schedule"]     = (int)count($horarios);
        $json["mention_schedule"]       = $horarios;
        $json["campana"]                = $campana_return;
        $json["cliente"]                = $cliente_return;
        $json["locutor"]                = $locutor;
        // $json["t_previas_no_leidas"]    = $t_previas_no_leidas;
        return new JsonModel($json);

    }

    public function ingresarAction()
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

        $this->dbAdapter    = $this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloFunctions    = new Modelofunctions($this->dbAdapter);
        $modeloMencion      = new Modelomenciones($this->dbAdapter);
        $modeloCampana      = new Modelocampanas($this->dbAdapter);
        $modeloHorario      = new Modelohorarios($this->dbAdapter);
        
        $json       =array();

        $resultValid=$this->validaCampos($_POST, 'INGRESAR');
        if($resultValid!="ok"){
            $json["status"]="error";
            $json["msj"]=$resultValid;
            return new JsonModel($json);
        }

        $idcamp=0;
        if( !isset($_POST['campanaHidden']) or !is_numeric($_POST['campanaHidden']) or abs($_POST['campanaHidden'])<=0 ){
            $json["status"]="ok";
            $json["msj"]="La campaña no existe.";
            return new JsonModel($json);
        }
        $idcamp=abs($_POST['campanaHidden']);

        try {
            $campana = $modeloCampana->get($idcamp);
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            $json["msj_e"]=(string)$e;
            return new JsonModel($json);
        }

        //ASIGNAMOS
        $mention_date_start =trim( $_POST["mention_date_start"] );
        $mention_date_end   =trim( $_POST["mention_date_end"] );
        $mention_days       =implode(',', $_POST["mention_days"]);

        $datos                          =array();
        $datos["idcamp"]                =$campana['idcamp'];
        $datos["mention_description"]   =addslashes($_POST["mention_description"]);
        $datos["mention_date_start"]    =$mention_date_start;
        $datos["mention_date_end"]      =$mention_date_end;
        $datos["mention_days"]          =$mention_days;
        $datos["mention_status"]        =1;
        $datos["mention_date_create"]   =new Expression('now()');
        

        //INGRESAMOS
        try {
            $ultimo=$modeloMencion->add($datos);
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            $json["msj_e"]=(string)$e;
            return new JsonModel($json);
        }

        if(is_array($_POST['mention_schedule'])){
            foreach ($_POST['mention_schedule'] as $key => $value) {
                try {
                    $datos_hora                 =array();
                    $datos_hora['idm']          =$ultimo;
                    $datos_hora['schedule_hour']=$value.':00';
                    $modeloHorario->add($datos_hora);
                } catch (\Exception $e) {
                    // NO HACEMOS NADA
                }
                
            }
        }
        
        $json["status"]="ok";
        $json["msj"]="Mención ingresada correctamente.";
        $json["t_menciones"]=abs($campana['t_menciones'])+1;
        $json["post"]=$_POST;

        return new JsonModel($json);
    }


    public function editarAction()
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

        $this->dbAdapter    = $this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloMencion      = new Modelomenciones($this->dbAdapter);
        $modeloFunctions    = new Modelofunctions($this->dbAdapter);
        $modeloHorario      = new Modelohorarios($this->dbAdapter);
        
        
        $json       =array();
        
        if (!isset($_POST["mencionHidden"]) ) {
            $json["status"]="error";
            $json["msj"]="Mención no encontrado.";
            return new JsonModel($json);
        }

        $idm=(int)$_POST["mencionHidden"];
        try {
            $mencion=$modeloMencion->get($idm);
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            return new JsonModel($json);
        }
        
        if ( $mencion==NULL ) {
            $json["status"]="error";
            $json["msj"]="Debe seleccionar una mención válida.";
            return new JsonModel($json);
        }

        $resultValid=$this->validaCampos($_POST, 'EDITAR');
        if($resultValid!="ok"){
            $json["status"]="error";
            $json["msj"]=$resultValid;
            return new JsonModel($json);
        } 

        //ASIGNAMOS
        $mention_date_start =trim( $_POST["mention_date_start"] );
        $mention_date_end   =trim( $_POST["mention_date_end"] );
        $mention_days       =implode(',', $_POST["mention_days"]);

        $datos                          =array();
        $datos["mention_description"]   =addslashes($_POST["mention_description"]);
        $datos["mention_date_start"]    =$mention_date_start;
        $datos["mention_date_end"]      =$mention_date_end;
        $datos["mention_days"]          =$mention_days;

        //EDITAMOS
        try {
            $modeloMencion->update($datos, $mencion["idm"]);
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            return new JsonModel($json);
        }

        //******************************************************************************************
        //ACTUALIZAMOS SOLO SI HAY ID Y HORARIOS
        if(is_array($_POST['mention_schedule_id']) and count($_POST['mention_schedule_id'])>0 
                and is_array($_POST['mention_schedule']) and count($_POST['mention_schedule'])>0 
                    and count($_POST['mention_schedule_id'])==count($_POST['mention_schedule']) ){

            $mention_schedule=$_POST['mention_schedule'];
            foreach ($_POST['mention_schedule_id'] as $key => $idsched){

                //VALIDAMOS SI EXISTE EL ID
                try {
                    $horario    = $modeloHorario->get($idsched);
                    $idsched    = $horario['idsched'];
                } catch (\Exception $e) {
                    $idsched='';
                }

                //SI EXISTE LO EDITAMOS
                if(!empty($idsched)){
                    try {
                        $datos_hora                 =array();
                        $datos_hora['schedule_hour']=$mention_schedule[$key].':00';
                        $modeloHorario->update($datos_hora, $idsched);
                    } catch (\Exception $e) {
                        // NO HACEMOS NADA
                    }
                }
                else{
                    //SI NO EXISTE LO AGREGAMOS
                    try {
                        $datos_hora                 =array();
                        $datos_hora['idm']          =$mencion['idm'];
                        $datos_hora['schedule_hour']=$mention_schedule[$key].':00';
                        $modeloHorario->add($datos_hora);
                    } catch (\Exception $e) {
                        // NO HACEMOS NADA
                    }
                }
            }
        }
        //******************************************************************************************


        $json["status"]="ok";
        $json["msj"]="Mención editada correctamente.";
        $json["post"]=$_POST;
        return new JsonModel($json);

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

        $this->dbAdapter    = $this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloMencion      = new Modelomenciones($this->dbAdapter);
        $modeloFunctions    = new Modelofunctions($this->dbAdapter);
        $modeloCampana      = new Modelocampanas($this->dbAdapter);


        $json       =array();
        if (!isset($_POST["mencion"]) ) {
            $json["status"]="error";
            $json["msj"]="Debe seleccionar un proyecto válido.";
            return new JsonModel($json);
        }
        try {
            $idm        = abs($_POST["mencion"]);
            $mencion    = $modeloMencion->get($idm);
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            return new JsonModel($json);
        }

        if ( $mencion==NULL ) {
            $json["status"]="error";
            $json["msj"]="Debe seleccionar una mención válida.";
            return new JsonModel($json);
        }

        try {
            $modeloMencion->delete( $mencion["idm"] );
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            return new JsonModel($json);
        }
        
        //*********************************************************
        //CONSULTAMOS LA CAMPAÑA DESPUES DE ELIMINAR
        try {

            $campana    = $modeloCampana->get($mencion['idcamp']);
            $t_menciones= abs($campana['t_menciones']);
        } catch (\Exception $e) {
            $t_menciones= 0;
        }
        //*********************************************************
        

        $json["status"]         = "ok";
        $json["msj"]            = "Mención eliminado correctamente.";
        $json["t_menciones"]    = $t_menciones;
        return new JsonModel($json);
    }


    public function changestatusAction()
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

        $this->dbAdapter=$this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloMencion=new Modelomenciones($this->dbAdapter);

        $json=array();

        if (!isset($_POST["mencion"]) ) {
            $json["status"]="error";
            $json["msj"]="Debe seleccionar un proyecto válido.";
            return new JsonModel($json);
        }
        try {
            $idm        = abs($_POST["mencion"]);
            $mencion    = $modeloMencion->get($idm);
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            return new JsonModel($json);
        }

        //==============================================================
        //VALIDA 
        if ( $mencion==NULL) {
            $json["status"]="error";
            $json["msj"]="La mención no existe.";
            return new JsonModel($json);
        }

        $mention_status=1;
        $msj="Mención habilitada correctamente.";
        if($mencion["mention_status"]==1){
            $mention_status=0;
            $msj="Mención deshabilitada correctamente.";
        }

        try {
            $datos                  =array();
            $datos["mention_status"]=$mention_status;

            $modeloMencion->update( $datos, $mencion["idm"] );
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            return new JsonModel($json);
        }

        $json["status"]="ok";
        $json["msj"]=$msj;
        return new JsonModel($json);
    }


    //=================================================
    //AUTOCOMPLETE
    public function autocompleteAction()
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


        $this->dbAdapter    = $this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloMencion      = new Modelomenciones($this->dbAdapter);
        

        $json=array();
        $autocomplete=array();
        
        $mention_description=null;
        if( isset($_POST["search"]) and !empty($_POST["search"]) ){
            $mention_description=addslashes( trim($_POST["search"]) );
        }
        try {
            $autocomplete_tmp=$modeloMencion->all(0, 4, null, $mention_description, 1, null, null, null, null);//start, limit
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            return new JsonModel($json);
        }

        //==============================================================
        //VALIDA 
        if ( $autocomplete_tmp!=NULL){
            foreach ($autocomplete_tmp as $value) {

                $autocomplete[]=array(
                    'nombre'        => stripslashes($value["mention_description"]),
                    'id'            => $value["idm"]
                );
            }
        }


        $json["status"]="ok";
        $json["msj"]="";
        $json["items"]=$autocomplete;
        return new JsonModel($json);
    }
}
