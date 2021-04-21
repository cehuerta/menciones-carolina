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

//Componentes de autenticación
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\Session as SessionStorage;
use Zend\Session\Container;


// *************************
//INCLUIR VALIDADOR
use Menciones\Form\validatorMenciones;
// *************************


// modelos
use Menciones\Model\Entity\Modelopautas;
use Menciones\Model\Entity\Modeloclientes;

use Application\Model\Entity\Modeloradios;
use Application\Model\Entity\Modelofunctions;

class PautasController extends AbstractActionController
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


    public function pautasAction()
    {
        $identidad=$this->validaSesion();
        if( $identidad==false ){
            return $this->redirect()->toRoute( 'login' );
        }
        $this->dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloFunctions = new Modelofunctions($this->dbAdapter);
        $modeloPauta     = new Modelopautas($this->dbAdapter);
        $modeloRadios    = new Modeloradios($this->dbAdapter);
        $radios          = array();

        if( (int)$identidad->tipo_user==1 ){
            try {
                $radios = $modeloRadios->all(null, null, 'r.radio_name ASC');
            } catch (\Exception $e) {}
        }

        //============================================================
        $page           = 1;
        $lengthTable    = 10;
        $search         = '';
        $startTable     = 0;
        $orderTable     = 0;
        $order_dirTable = 'desc';
        if( isset($_GET["search"]) and !empty($_GET["search"]) ){
            $search=urldecode($_GET["search"]);
        }
        if( isset($_GET["length"]) and (int)$_GET["length"]>10 ){
            $lengthTable=(int)$_GET["length"];
        }
        if( isset($_GET["page"]) and (int)$_GET["page"]>1 ){
            $page=(int)$_GET["page"];
        }
        if( isset($_GET["order"]) and (int)$_GET["order"]>=0 and (int)$_GET["order"]<=3 ){
            $orderTable=(int)$_GET["order"];
        }
        if( isset($_GET["order_dir"]) and !empty($_GET["order_dir"]) 
                and (urldecode($_GET["order_dir"])=='asc' or urldecode($_GET["order_dir"])=='desc') ){
            $order_dirTable=urldecode($_GET["order_dir"]);
        }
        $startTable=($page-1)*$lengthTable;
        //============================================================

        return new ViewModel(array(
            'NoImage'        => $this->rutas["NoImage"],
            'startTable'     => $startTable,
            'lengthTable'    => $lengthTable,
            'search'         => $search,
            'orderTable'     => $orderTable,
            'order_dirTable' => $order_dirTable,
            'radios'         => $radios,
            'usuario'        => $identidad
        ));  
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

        $this->dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloPauta     = new Modelopautas($this->dbAdapter);
        $modeloRadios    = new Modeloradios($this->dbAdapter);
        

        $json=array();
        if (!isset($_POST["pauta"]) ) {
            $json["status"] = "error";
            $json["msj"]    = "La pauta no existe.";
            return new JsonModel($json);
        }

        try {
            $idpauta              = abs($_POST["pauta"]);
            $arg_query            = [];
            $arg_query['idpauta'] = $idpauta;
            $pauta                = $modeloPauta->get($arg_query);
            
            //==============================================================
            //VALIDA
            if ( $pauta==NULL) {
                $json["status"]="error";
                $json["msj"]="La campaña no existe.";
                return new JsonModel($json);
            }
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor inténtelo de nuevo más tarde.";
            return new JsonModel($json);
        }


        //==============================================================
        //VALIDA
        $radio_arr['id']  =0;
        $radio_arr['name']='';
        if ( !empty($pauta['radio_name']) ) {
            $radio_arr['id']  = $pauta["idr"];
            $radio_arr['name']= stripslashes($pauta["radio_name"]);
        }

        $hora_pauta = explode(':', $pauta["hora_pauta"]);
        $hora_pauta = $hora_pauta[0].':'.$hora_pauta[1];
        

        $json["status"]                     = "ok";
        $json["msj"]                        = "";
        $json['pauta']["id"]                = $pauta["idpauta"];
        $json['pauta']["radio"]             = $radio_arr;
        $json['pauta']["title_pauta"]       = stripslashes($pauta["title_pauta"]);
        $json['pauta']["descripcion_pauta"] = stripslashes($pauta["descripcion_pauta"]);
        $json['pauta']["dia_pauta"]         = $pauta["dia_pauta"];
        $json['pauta']["dia_pauta_f"]       = $pauta["dia_pauta_f"];
        $json['pauta']["hora_pauta"]        = $pauta["hora_pauta"];
        $json['pauta']["hora_pauta_f"]      = $pauta["hora_pauta_f"];
        $json['pauta']["pauta_leida"]       = (int)$pauta["pauta_leida"];
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

        $this->dbAdapter          = $this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloFunctions          = new Modelofunctions($this->dbAdapter);
        $modeloPauta              = new Modelopautas($this->dbAdapter);
        $modeloRadios             = new Modeloradios($this->dbAdapter);
        $modeloValidatorMenciones = new validatorMenciones();
        $json                     = array();

        $resultValid = $modeloValidatorMenciones->validate($_POST, 'pauta_ingresar');
        if($resultValid!="ok"){
            $json["status"] = "error";
            $json["msj"]    = $resultValid;
            return new JsonModel($json);
        }

        //***********************************************************
        //VALIDAMOS
        try {

            if( $identidad->tipo_user!=1 ){
                $idr = $identidad->idr;
            }
            else{
                if( !isset($_POST["radio"]) or !is_numeric($_POST["radio"]) or abs($_POST["radio"])<=0 or strlen($_POST["radio"])>11 ){
                    $json["status"] = "error";
                    $json["msj"]    = "Seleccione una radio válida.";
                    return new JsonModel($json);
                }
                $idr = abs($_POST['radio']);
            }
            $radio = $modeloRadios->get($idr);
            
            if($radio==null){
                $json["status"]="error";
                $json["msj"]="La radio seleccionada no es válida.";
                return new JsonModel($json);
            }
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor inténtelo de nuevo más tarde.";
            return new JsonModel($json);
        }
        //***********************************************************


        
        //INGRESAMOS
        try {
            
            $datos                      = array();
            $datos["idr"]               = $radio["idr"];
            $datos["title_pauta"]       = addslashes(trim( $_POST["title_pauta"] ));
            $datos["descripcion_pauta"] = null;
            $datos["fecha_pauta"]       = new Expression('now()');
            $datos["dia_pauta"]         = trim($_POST["dia_pauta"]);
            $datos["hora_pauta"]        = trim($_POST["hora_pauta"]).':00';
            $datos["pauta_leida"]       = 0;

            if(isset($_POST['descripcion_pauta']) and !empty($_POST['descripcion_pauta']) and strlen($_POST['descripcion_pauta'])<=65000 )
                $datos["descripcion_pauta"] = addslashes(trim($_POST['descripcion_pauta']));


            $ultimo = $modeloPauta->add($datos);
        } catch (\Exception $e) {
            $json["status"] = "error";
            $json["msj"]    = "Ocurrio un error mientras se procesaba su solicitud, por favor inténtelo de nuevo más tarde.";
            return new JsonModel($json);
        }
        
        $json["status"] = "ok";
        $json["msj"]    = "Pauta ingresada correctamente.";
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

        $this->dbAdapter          = $this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloPauta              = new Modelopautas($this->dbAdapter);
        $modeloFunctions          = new Modelofunctions($this->dbAdapter);
        $modeloRadios             = new Modeloradios($this->dbAdapter);
        $modeloValidatorMenciones = new validatorMenciones();
        $json                     = array();

        //***********************************************************
        $resultValid = $modeloValidatorMenciones->validate($_POST, 'pauta_ingresar');
        if($resultValid!="ok"){
            $json["status"] = "error";
            $json["msj"]    = $resultValid;
            return new JsonModel($json);
        }
        //***********************************************************


        //***********************************************************
        //VALIDAMOS
        try {
            
            $idpauta              = abs($_POST["pauta"]);
            $arg_query            = [];
            $arg_query['idpauta'] = $idpauta;
            $pauta                = $modeloPauta->get($arg_query);
            if ( $pauta==NULL ) {
                $json["status"] ="error";
                $json["msj"]    ="Debe seleccionar un pauta válida.";
                return new JsonModel($json);
            }

            if( $identidad->tipo_user!=1 ){
                $idr = $identidad->idr;
            }
            else{
                if( !isset($_POST["radio"]) or !is_numeric($_POST["radio"]) or abs($_POST["radio"])<=0 or strlen($_POST["radio"])>11 ){
                    $json["status"] = "error";
                    $json["msj"]    = "Seleccione una radio válida.";
                    return new JsonModel($json);
                }
                $idr = abs($_POST['radio']);
            }
            $radio = $modeloRadios->get($idr);
            if($radio==null){
                $json["status"]="error";
                $json["msj"]="La radio seleccionada no es válida.";
                return new JsonModel($json);
            }
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor inténtelo de nuevo más tarde.";
            return new JsonModel($json);
        }
        //***********************************************************



        //EDITAMOS
        try {

            $datos                      = array();
            $datos["idr"]               = $radio["idr"];
            $datos["title_pauta"]       = addslashes(trim( $_POST["title_pauta"] ));
            $datos["dia_pauta"]         = trim( $_POST["dia_pauta"] );
            $datos["hora_pauta"]        = trim( $_POST["hora_pauta"] ).':00';
            $datos["descripcion_pauta"] = null;

            if(isset($_POST['descripcion_pauta']) and !empty($_POST['descripcion_pauta']) and strlen(trim($_POST['descripcion_pauta']))==7 )
                $datos["descripcion_pauta"]=addslashes(trim($_POST['descripcion_pauta']));


            $modeloPauta->update($datos, $pauta["idpauta"]);
        } catch (\Exception $e) {
            $json["status"] = "error";
            $json["msj"]    = "Ocurrio un error mientras se procesaba su solicitud, por favor inténtelo de nuevo más tarde.";
            return new JsonModel($json);
        }

        $json["status"] = "ok";
        $json["msj"]    = "Pauta editada correctamente.";
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

        $this->dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloPauta     = new Modelopautas($this->dbAdapter);
        $modeloFunctions = new Modelofunctions($this->dbAdapter);
        $modeloRadios    = new Modeloradios($this->dbAdapter);
        $json            = array();

        if (!isset($_POST["pauta"]) ) {
            $json["status"] = "error";
            $json["msj"]    = "Debe seleccionar una campaña válida.";
            return new JsonModel($json);
        }


        try {

            $idpauta              = abs($_POST["pauta"]);
            $arg_query            = [];
            $arg_query['idpauta'] = $idpauta;
            $pauta                = $modeloPauta->get($arg_query);
            
            if ( $pauta==NULL ) {
                $json["status"] = "error";
                $json["msj"]    = "Debe seleccionar una campaña válida.";
                return new JsonModel($json);
            }

            $arg_query            = [];
            $arg_query['idpauta'] = $pauta['idpauta'];
            $modeloPauta->delete( $arg_query );
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor inténtelo de nuevo más tarde.";
            return new JsonModel($json);
        }



        $json["status"] = "ok";
        $json["msj"]    = "Pauta eliminada correctamente.";
        return new JsonModel($json);
    }



    //=================================================
    //FILTRO AUTOCOMPLETE
    public function filtroAction()
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


        $this->dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloPauta     = new Modelopautas($this->dbAdapter);
        $modeloRadios    = new Modeloradios($this->dbAdapter);
        

        $json        = array();
        $registros   = array();
        $idr         = null;
        $search      = null;
        $dia_pauta   = null;
        $hora_pauta  = null;
        $pauta_leida = null;
        $start       = 0;
        $limit       = 10;
        $order       = null;
        $arg_query   = [];
        if( (int)$identidad->tipo_user!=1 )
            $idr = $identidad->idr;

        if( isset($_POST["search"]) and !empty($_POST["search"]) )
            $search = addslashes( trim($_POST["search"]) );

        if( isset($_POST["dia_pauta"]) and !empty($_POST["dia_pauta"]) )
            $dia_pauta = trim($_POST["dia_pauta"]);
        
        if( isset($_POST["hora_pauta"]) and !empty($_POST["hora_pauta"]) )
            $hora_pauta = trim($_POST["hora_pauta"]);

        if( isset($_POST["pauta_leida"]) and !empty($_POST["pauta_leida"]) )
            $pauta_leida = abs($_POST["pauta_leida"]);

        try {
            $args_query                = array();
            $args_query['idr']         = $idr;
            $args_query['search']      = $search;
            $args_query['dia_pauta']   = $dia_pauta;
            $args_query['hora_pauta']  = $hora_pauta;
            $args_query['pauta_leida'] = $pauta_leida;
            $args_query['start']       = $start;
            $args_query['limit']       = $limit;
            $args_query['order']       = $order;
            $registros_tmp             = $modeloPauta->all($args_query);
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor inténtelo de nuevo más tarde.";
            return new JsonModel($json);
        }

        //==============================================================
        //VALIDA
        if ( $registros_tmp!=NULL){
            foreach ($registros_tmp as $value) {

                $registros[]=array(
                    'nombre' => stripslashes($value["title_pauta"]),
                    'id'     => $value["idpauta"]
                );
            }
        }

        $json["status"]    = "ok";
        $json["msj"]       = "";
        $json["registros"] = $registros;
        return new JsonModel($json);
    }
}
