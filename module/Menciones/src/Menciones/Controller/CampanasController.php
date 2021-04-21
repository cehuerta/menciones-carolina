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


// modelos
use Menciones\Model\Entity\Modelocampanas;
use Menciones\Model\Entity\Modeloclientes;

use Application\Model\Entity\Modeloradios;
use Application\Model\Entity\Modelofunctions;

class CampanasController extends AbstractActionController
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

        if( !isset($datos["campaign_title"]) or empty($datos["campaign_title"]) or strlen($datos["campaign_title"])>200 ){
            return "Debe ingresar el nombre de la campaña (Entre 1-200 caracteres).";
        }
        if($action=='INGRESAR'){
            // if( !isset($datos["campaign_slug"]) or empty($datos["campaign_slug"]) or strlen($datos["campaign_slug"])>200 ){
            //     return "Debe ingresar un slug válido para la campaña (Entre 1-200 caracteres).";
            // }
        }
        if( !isset($datos["cliente"]) or empty($datos["cliente"]) or abs($datos["cliente"])<=0  or strlen($datos["cliente"])>11 ){
            return "Debe ingresar el cliente de la campaña.";
        }

        return 'ok';
    }

    public function campanasAction()
    {
        $identidad=$this->validaSesion();
        if( $identidad==false ){
            return $this->redirect()->toRoute( 'login' );
        }
        $this->dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloFunctions = new Modelofunctions($this->dbAdapter);
        $modeloCampana   = new Modelocampanas($this->dbAdapter);
        $modeloRadios    = new Modeloradios($this->dbAdapter);

        $radios = array();
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
        $orderTable     = 3;
        $order_dirTable = 'desc';
        if( isset($_GET["campana"]) and !empty($_GET["campana"]) ){
            $search=urldecode($_GET["campana"]);
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

    public function detalleAction()
    {
        $identidad=$this->validaSesion();
        if( $identidad==false ){
            return $this->redirect()->toRoute( 'login' );
        }
        $this->dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloCampana   = new Modelocampanas($this->dbAdapter);
        $modeloCliente   = new Modeloclientes($this->dbAdapter);

        $idcamp=abs( $this->params()->fromRoute('id',null) );
        if ($idcamp==NULL) {
            $this->flashMessenger()->setNamespace("msjError")->addMessage("Campaña no declarada.");
            return $this->redirect()->toRoute( 'campanas' );
        }

        try {
            $campana=$modeloCampana->get($idcamp);
        } catch (\Exception $e) {
            $this->flashMessenger()->setNamespace("msjError")->addMessage("Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.");
            return $this->redirect()->toRoute( 'campanas' );
        }

        if( $campana==null ){
            $this->flashMessenger()->setNamespace("msjError")->addMessage("Campaña no encontrada.");
            return $this->redirect()->toRoute( 'campanas' );
        }

        if( (int)$identidad->tipo_user!=1 ){
            if( $identidad->idr!=$campana['idr'] ){
                $this->flashMessenger()->setNamespace("msjError")->addMessage("Campaña no encontrada 2.");
                return $this->redirect()->toRoute( 'campanas' );
            }
        }

        //============================================================
        $queryGET       ="";
        $page           =1;
        $lengthTable    =10;
        $search         ='';
        $orderTable     =3;
        $order_dirTable ='desc';
        if( isset($_GET["campana"]) and !empty($_GET["campana"]) ){
            $search=$_GET["campana"];
        }
        if( isset($_GET["length"]) and (int)$_GET["length"]>10 ){
            $lengthTable=(int)$_GET["length"];
        }
        if( isset($_GET["page"]) and (int)$_GET["page"]>1 ){
            $page=(int)$_GET["page"];
        }
        if( isset($_GET["order"]) and (int)$_GET["order"]>=0 ){
            $orderTable=(int)$_GET["order"];
        }
        if( isset($_GET["order_dir"]) and !empty($_GET["order_dir"]) 
                and (urldecode($_GET["order_dir"])=='asc' or urldecode($_GET["order_dir"])=='desc') ){
            $order_dirTable=urldecode($_GET["order_dir"]);
        }

        $queryGET='?campana='.urlencode($search).'&length='.$lengthTable.'&page='.$page.'&order='.$orderTable.'&order_dir='.$order_dirTable;
        //============================================================

        return new ViewModel(array(
            'NoImage'           =>$this->rutas["NoImage"],
            'campana'           =>$campana,
            'queryGET'          =>$queryGET
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

        $this->dbAdapter=$this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloCampana=new Modelocampanas($this->dbAdapter);
        $modeloCliente=new Modeloclientes($this->dbAdapter);
        

        $json=array();
        if (!isset($_POST["campana"]) ) {
            $json["status"]="error";
            $json["msj"]="La campaña no existe.";
            return new JsonModel($json);
        }

        try {
            $idcamp     = (int)$_POST["campana"];
            $campana    = $modeloCampana->get($idcamp);
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            return new JsonModel($json);
        }
        //==============================================================
        //VALIDA
        if ( $campana==NULL) {
            $json["status"]="error";
            $json["msj"]="La campaña no existe.";
            return new JsonModel($json);
        }

        try {
            $cliente    = $modeloCliente->get($campana["idc"]);
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            return new JsonModel($json);
        }
        //==============================================================
        //VALIDA
        $cliente_arr['id']  =0;
        $cliente_arr['name']='';
        if ( $cliente!=NULL) {
            $cliente_arr['id']  =$cliente["idc"];
            $cliente_arr['name']=stripslashes($cliente["client_name"]);
        }
        

        $json["status"]             ="ok";
        $json["msj"]                ="";
        $json["id"]                 =$campana["idcamp"];
        $json["cliente"]            =$cliente_arr;
        $json["campaign_title"]     =stripslashes($campana["campaign_title"]);
        $json["campaign_slug"]      =stripslashes($campana["campaign_slug"]);
        $json["campaign_color"]     =stripslashes($campana["campaign_color"]);
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

        $this->dbAdapter        = $this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloFunctions        = new Modelofunctions($this->dbAdapter);
        $modeloCampana          = new Modelocampanas($this->dbAdapter);
        $modeloCliente          = new Modeloclientes($this->dbAdapter);
        
        $json       =array();

        $resultValid=$this->validaCampos($_POST, 'INGRESAR');
        if($resultValid!="ok"){
            $json["status"]="error";
            $json["msj"]=$resultValid;
            return new JsonModel($json);
        }

        //***********************************************************
        //VALIDAMOS
        try {
            $idc        = abs($_POST["cliente"]);
            $cliente    = $modeloCliente->get($idc);
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            return new JsonModel($json);
        }
        if($cliente==null){
            $json["status"]="error";
            $json["msj"]="El cliente seleccionado no es válido.";
            return new JsonModel($json);
        }
        //***********************************************************

        //SLUG 
        $contSlugName=0;
        $cont=1;
        $slug="";
        $tmp="";
        do {
            if ($contSlugName==0) {
                $slug=mb_strtolower($modeloFunctions->sanear_string( trim($_POST["campaign_title"]) ), 'UTF-8');
                $tmp=$slug;
            }else{
                $slug=$tmp;
                $slug.="-".$cont;
            }
            $contSlugName=$modeloCampana->contCustom($slug, null, null, null, null);
            $cont=$cont+1;
        } while ($contSlugName != 0);
        //FIN SLUG

        $datos                         = array();
        $datos["idc"]                  = $cliente["idc"];
        $datos["campaign_slug"]        = $slug;
        $datos["campaign_title"]       = addslashes(trim( $_POST["campaign_title"] ));
        $datos["campaign_date_create"] = new Expression('now()');
        $datos["campaign_status"]      = 1;
        $datos["idr"]                  = $cliente['idr'];

        if(isset($_POST['campaign_color']) and !empty($_POST['campaign_color']) and strlen(trim($_POST['campaign_color']))==7 )
            $datos["campaign_color"]=addslashes(trim($_POST['campaign_color']));

        //INGRESAMOS
        try {
            $ultimo     = $modeloCampana->add($datos);
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            return new JsonModel($json);
        }
        
        $json["status"]="ok";
        $json["msj"]="Campaña ingresada correctamente.";
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
        $modeloCampana      = new Modelocampanas($this->dbAdapter);
        $modeloFunctions    = new Modelofunctions($this->dbAdapter);
        $modeloCliente      = new Modeloclientes($this->dbAdapter);
        
        
        $json       =array();
        if (!isset($_POST["campanaHidden"]) ) {
            $json["status"]="error";
            $json["msj"]="Campaña no encontrada.";
            return new JsonModel($json);
        }

        try {
            $idcamp     = abs($_POST["campanaHidden"]);
            $campana    = $modeloCampana->get($idcamp);
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            return new JsonModel($json);
        }
        if ( $campana==NULL ) {
            $json["status"]="error";
            $json["msj"]="Debe seleccionar un campaña válido.";
            return new JsonModel($json);
        }

        $resultValid=$this->validaCampos($_POST, 'EDITAR');
        if($resultValid!="ok"){
            $json["status"]="error";
            $json["msj"]=$resultValid;
            return new JsonModel($json);
        } 

        //***********************************************************
        //VALIDAMOS
        try {
            $idc        = abs($_POST["cliente"]);
            $cliente    = $modeloCliente->get($idc);
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            return new JsonModel($json);
        }
        if($cliente==null){
            $json["status"]="error";
            $json["msj"]="El cliente seleccionado no es válido.";
            return new JsonModel($json);
        }
        //***********************************************************

        $datos                   = array();
        $datos["idc"]            = $cliente["idc"];
        $datos["campaign_title"] = addslashes(trim( $_POST["campaign_title"] ));
        $datos["idr"]            = $cliente['idr'];

        if(isset($_POST['campaign_color']) and !empty($_POST['campaign_color']) and strlen(trim($_POST['campaign_color']))==7 )
            $datos["campaign_color"]=addslashes(trim($_POST['campaign_color']));

        //EDITAMOS
        try {
            $modeloCampana->update($datos, $campana["idcamp"]);
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            return new JsonModel($json);
        }

        $json["status"]="ok";
        $json["msj"]="Campaña editada correctamente.";
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

        $this->dbAdapter        = $this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloCampana          = new Modelocampanas($this->dbAdapter);
        $modeloFunctions        = new Modelofunctions($this->dbAdapter);

        $json       =array();
        if (!isset($_POST["campana"]) ) {
            $json["status"]="error";
            $json["msj"]="Debe seleccionar una campaña válida.";
            return new JsonModel($json);
        }

        try {
            $idcamp     = (int)$_POST["campana"];
            $campana    = $modeloCampana->get($idcamp);
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            return new JsonModel($json);
        }

        if ( $campana==NULL ) {
            $json["status"]="error";
            $json["msj"]="Debe seleccionar una campaña válida.";
            return new JsonModel($json);
        }

        try {
            $modeloCampana->delete( $campana["idcamp"] );
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            return new JsonModel($json);
        }

        $json["status"]="ok";
        $json["msj"]="Campaña eliminada correctamente.";
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

        $this->dbAdapter    = $this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloCampana      = new Modelocampanas($this->dbAdapter);

        $json=array();

        if (!isset($_POST["campana"]) ) {
            $json["status"]="error";
            $json["msj"]="La campaña no existe.";
            return new JsonModel($json);
        }

        try {
            $idcamp     = abs($_POST["campana"]);
            $campana    = $modeloCampana->get($idcamp);
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrio un error mientras se procesaba su solicitud, por favor intentelo de nuevo mas tarde.";
            return new JsonModel($json);
        }

        //==============================================================
        //VALIDA 
        if ( $campana==NULL) {
            $json["status"]="error";
            $json["msj"]="La campaña no existe.";
            return new JsonModel($json);
        }

        $campaign_status=1;
        $msj="Campaña habilitada correctamente.";
        if($campana["campaign_status"]==1){
            $campaign_status=0;
            $msj="Campaña deshabilitada correctamente.";
        }

        try {
            $datos                      =array();
            $datos["campaign_status"]   =$campaign_status;

            $modeloCampana->update( $datos, $campana["idcamp"] );
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
        $modeloCampana      = new Modelocampanas($this->dbAdapter);
        

        $json         = array();
        $autocomplete = array();
        $idr          = null;
        if( (int)$identidad->tipo_user!=1 )
            $idr = $identidad->idr;

        $campaign_title=null;
        if( isset($_POST["search"]) and !empty($_POST["search"]) ){
            $campaign_title=addslashes( trim($_POST["search"]) );
        }

        try {
            $autocomplete_tmp=$modeloCampana->all(0, 10,$campaign_title, 1, null, null, null, $idr);//
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
                    'nombre'        => stripslashes($value["campaign_title"]),
                    'id'            => $value["idcamp"]
                );
            }
        }

        $json["status"]="ok";
        $json["msj"]="";
        $json["items"]=$autocomplete;
        return new JsonModel($json);
    }
}
