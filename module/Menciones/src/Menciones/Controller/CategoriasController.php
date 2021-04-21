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

use Zend\View\Renderer\PhpRenderer as PhpRenderer;
use Zend\View\Resolver\TemplateMapResolver as TemplateMapResolver;

use Zend\Db\Sql\Predicate\Expression;

//Componentes de autenticación
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\Session as SessionStorage;
use Zend\Session\Container;

// modelos
use Menciones\Model\Entity\Modelocategorias;

use Application\Model\Entity\Modeloradios;
use Application\Model\Entity\Modelofunctions;

class CategoriasController extends AbstractActionController
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

        if( !isset($datos["category_name"]) or empty($datos["category_name"]) or strlen($datos["category_name"])>200 ){
            return "Debe ingresar un nombre válido (Entre 1-200 caracteres).";
        }
        if($action=='INGRESAR'){
            // if( !isset($datos["client_slug"]) or empty($datos["client_slug"]) or strlen($datos["client_slug"])>200 ){
            //     return "Debe ingresar un slug válido para el cliente (Entre 1-200 caracteres).";
            // }
        }
        return 'ok';
    }

    public function categoriasAction()
    {
        $identidad=$this->validaSesion();
        if( $identidad==false ){
            return $this->redirect()->toRoute( 'login' );
        }
        
        $this->dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloFunctions = new Modelofunctions($this->dbAdapter);
        $modeloCategoria = new Modelocategorias($this->dbAdapter);
        $modeloRadios    = new Modeloradios($this->dbAdapter);
        
        $radios = array();
        if( (int)$identidad->tipo_user==1 ){
            try {
                $radios = $modeloRadios->all(null, null, 'r.radio_name ASC');
            } catch (\Exception $e) {}
        }

        //============================================================
        $page=1;
        $lengthTable=10;
        $search='';
        $startTable=0;
        $orderTable=1;
        $order_dirTable='desc';

        if( isset($_GET["categoria"]) and !empty($_GET["categoria"]) ){
            $search=urldecode($_GET["categoria"]);
        }
        if( isset($_GET["length"]) and (int)$_GET["length"]>10 ){
            $lengthTable=(int)$_GET["length"];
        }
        if( isset($_GET["page"]) and (int)$_GET["page"]>1 ){
            $page=(int)$_GET["page"];
        }
        if( isset($_GET["order"]) and (int)$_GET["order"]>=0 and (int)$_GET["order"]<=2 ){
            $orderTable=(int)$_GET["order"];
        }
        if( isset($_GET["order_dir"]) and !empty($_GET["order_dir"]) 
                and (urldecode($_GET["order_dir"])=='asc' or urldecode($_GET["order_dir"])=='desc') ){
            $order_dirTable=urldecode($_GET["order_dir"]);
        }
        $startTable=($page-1)*$lengthTable;
        //============================================================

        return new ViewModel(array(
            'startTable'     => $startTable,
            'lengthTable'    => $lengthTable,
            'search'         => $search,
            'orderTable'     => $orderTable,
            'order_dirTable' => $order_dirTable,
            'NoImage'        => $this->rutas["NoImage"],
            'radios'         => $radios,
            'usuario'        => $identidad
        ));  
    }

    public function exportarAction()
    {
        $identidad=$this->validaSesion();
        if( $identidad==false ){
            return $this->redirect()->toRoute( 'login' );
        }
        $this->dbAdapter=$this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloFunctions=new Modelofunctions($this->dbAdapter);
        $modeloCategoria=new Modelocategorias($this->dbAdapter);

        require_once './vendor/libreriaphpexcel/phpexcel/Classes/PHPExcel/IOFactory.php';
        require_once './vendor/libreriaphpexcel/phpexcel/Classes/PHPExcel.php';
        require_once './vendor/libreriaphpexcel/phpexcel/Classes/PHPExcel/Reader/Excel2007.php';

        $objPHPExcel = new \PHPExcel();
        $objFecha = new \PHPExcel_Shared_Date();
        $objPHPExcel->setActiveSheetIndex(0);

        $fila=2;
        $i=0;
        $count_save=0;
        $categorias=$modeloCategoria->all(null, null, null);

        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, 1, 'CATEGORIA' );
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(1, 1, 'FECHA' );

        //COLOR DE FONDO
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('f97d21');
        $objPHPExcel->getActiveSheet()->getStyle('B1')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('f97d21');

        //COLOR TEXTO
        $styleArray = array(
            'font'  => array(
                'bold'  => true,
                'color' => array('rgb' => 'ffffff'),
            ));

        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($styleArray);
        $objPHPExcel->getActiveSheet()->getStyle('B1')->applyFromArray($styleArray);

        foreach ($categorias as $key => $value) {

            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($i, $fila, stripslashes($value["category_name"]) );
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($i+1, $fila, $value["category_date_create_fecha"].' | '.$value["category_date_create_hora"] );

            $fila=$fila+1;
        }

        $sheet = $objPHPExcel->getActiveSheet();
        $cellIterator = $sheet->getRowIterator()->current()->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells( true );
        /** @var PHPExcel_Cell $cell */
        foreach( $cellIterator as $cell ) {
            $sheet->getColumnDimension( $cell->getColumn() )->setAutoSize( true );
        }

        $fec=date("Ymd-His");
        $nameExcel="categorias_".$fec.".xls";
        header("Content-Type: application/vnd.ms-excel; name='excel' ");
        header("Content-Disposition: attachment; filename=".$nameExcel."");
        header("Cache-Control: max-age-0");
        header("Expires: 0");
        $objPHPExcel->getActiveSheet()->setTitle("Categorias");
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->setIncludeCharts(TRUE);
        // $objWriter->save($rutaSave.'/'.$nameExcel);
        $objWriter->save("php://output");
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
        $modeloCategoria = new Modelocategorias($this->dbAdapter);
        $modeloRadios    = new Modeloradios($this->dbAdapter);

        $json=array();
        if (!isset($_POST["categoria"]) ) {
            $json["status"]="error";
            $json["msj"]="La categoría no existe.";
            return new JsonModel($json);
        }

        $idca=(int)$_POST["categoria"];
        $categoria=$modeloCategoria->get($idca);

        //==============================================================
        //VALIDA QUE LA CATEGORIA SEA DEL USUARIO
        if ( $categoria==NULL) {
            $json["status"]="error";
            $json["msj"]="La categoría no existe.";
            return new JsonModel($json);
        }

        //****************************************************************
        //VALIDAMOS LA RADIO Y ASIGNAMOS LA RADIO SEGUN EL TIPO DE USUARIO
        // $idr=null;
        // $return_radio = array();
        // if( (int)$identidad->tipo_user==1 ){
            
        //     $idr   = (int)$cliente['idr'];
        //     $radio = null;
        //     try {
        //         $radio = $modeloRadios->get( $idr );
        //     } catch (\Exception $e) {}
        //     if($radio!=null){
        //         $return_radio = array(
        //             'id'   => $radio['idr'],
        //             'name' => $radio['radio_name']
        //         );
        //     }
            
        // }
        //****************************************************************

        $json["status"]        = "ok";
        $json["msj"]           = "";
        $json["id"]            = $categoria["idca"];
        $json["category_name"] = stripslashes($categoria["category_name"]);
        $json["category_slug"] = stripslashes($categoria["category_slug"]);
        // $json['radio']       = $return_radio;
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

        $this->dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloCategoria = new Modelocategorias($this->dbAdapter);
        $modeloFunctions = new Modelofunctions($this->dbAdapter);
        $modeloRadios    = new Modeloradios($this->dbAdapter);

        $json           =array();
        $resultValid    =$this->validaCampos($_POST, 'INGRESAR');
        if($resultValid!="ok"){
            $json["status"]="error";
            $json["msj"]=$resultValid;
            return new JsonModel($json);
        }

        //****************************************************************
        //VALIDAMOS LA RADIO Y ASIGNAMOS LA RADIO SEGUN EL TIPO DE USUARIO
        // if( (int)$identidad->tipo_user==1 ){
        //     if( !isset($_POST['radio']) || !is_numeric($_POST["radio"]) || (int)$_POST["radio"]<=0){
        //         $json["status"] = "error";
        //         $json["msj"]    = 'Debe seleccionar una radio para el cliente.';
        //         return new JsonModel($json);
        //     }
        //     $idr = (int)$_POST['radio'];
        //     try {
        //         $radio = $modeloRadios->get( $idr );
        //     } catch (\Exception $e) {
        //         $json["status"]="error";
        //         $json["msj"]="Se produjo un error al válidar la información, por favor intentelo de nuevo mas tarde.";
        //         return new JsonModel($json);
        //     }
        //     if($radio==null){
        //         $json["status"]="error";
        //         $json["msj"]="La radio seleccionada no existe.";
        //         return new JsonModel($json);
        //     }
        // }
        // else{
        //     if(empty($identidad->idr)){
        //         $json["status"]="error";
        //         $json["msj"]="No cuentas con una radio asignada, comunicate con tu administrador de sistema para que te asocie una.";
        //         return new JsonModel($json);
        //     }
        //     $idr = $identidad->idr;
        // }
        //****************************************************************
        
        //SLUG
        $contSlugName=0;
        $cont=1;
        $slug="";
        $tmp="";
        do {

            if ($contSlugName==0) {
                $slug=mb_strtolower($modeloFunctions->sanear_string( trim($_POST["category_name"]) ), 'UTF-8');
                $tmp=$slug;
            }else{
                $slug=$tmp;
                $slug.="-".$cont;
            }
            $contSlugName=$modeloCategoria->contCustom($slug, null );
            $cont=$cont+1;
        } while ($contSlugName != 0);
        //FIN SLUG

        $datos                         = array();
        $datos["category_slug"]        = $slug;
        $datos["category_name"]        = addslashes(trim( $_POST["category_name"] ));
        $datos["category_date_create"] = new Expression('now()');
        // $datos["idr"]                 = $idr;
        
        //INGRESAMOS
        $ultimo                       = $modeloCategoria->add($datos);

        $json["status"]="ok";
        $json["msj"]="Categoría ingresada correctamente.";
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

        $this->dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloCategoria = new Modelocategorias($this->dbAdapter);
        $modeloFunctions = new Modelofunctions($this->dbAdapter);
        $modeloRadios    = new Modeloradios($this->dbAdapter);        
        
        $json           =array();
        if (!isset($_POST["categoriaHidden"]) ) {
            $json["status"]="error";
            $json["msj"]="Categoría no encontrada.";
            return new JsonModel($json);
        }

        $idca=(int)$_POST["categoriaHidden"];
        $categoria=$modeloCategoria->get($idca);
        if ( $categoria==NULL ) {
            $json["status"]="error";
            $json["msj"]="Debe seleccionar una categoría válida.";
            return new JsonModel($json);
        }

        $resultValid=$this->validaCampos($_POST, 'EDITAR');
        if($resultValid!="ok"){
            $json["status"]="error";
            $json["msj"]=$resultValid;
            return new JsonModel($json);
        }

        //****************************************************************
        //VALIDAMOS LA RADIO Y ASIGNAMOS LA RADIO SEGUN EL TIPO DE USUARIO
        // $idr=null;
        // if( (int)$identidad->tipo_user==1 ){
        //     if( !isset($_POST['radio']) || !is_numeric($_POST["radio"]) || (int)$_POST["radio"]<=0){
        //         $json["status"] = "error";
        //         $json["msj"]    = 'Debe seleccionar una radio para el cliente.';
        //         return new JsonModel($json);
        //     }
        //     $idr = (int)$_POST['radio'];
        //     try {
        //         $radio = $modeloRadios->get( $idr );
        //     } catch (\Exception $e) {
        //         $json["status"]="error";
        //         $json["msj"]="Se produjo un error al válidar la información, por favor intentelo de nuevo mas tarde.";
        //         return new JsonModel($json);
        //     }
        //     if($radio==null){
        //         $json["status"]="error";
        //         $json["msj"]="La radio seleccionada no existe.";
        //         return new JsonModel($json);
        //     }
        // }
        //****************************************************************
        $datos                  = array();
        $datos["category_name"] = addslashes(trim( $_POST["category_name"] ));
        // if($idr!=null)
        //     $datos["idr"] = $idr;

        //EDITAMOS
        $modeloCategoria->update($datos, $categoria["idca"]);

        $json["status"]="ok";
        $json["msj"]="Categoría editada correctamente.";
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
        $modeloCategoria = new Modelocategorias($this->dbAdapter);
        
        $json = array();
        if (!isset($_POST["categoria"]) ) {
            $json["status"]="error";
            $json["msj"]="Debe seleccionar una categoría válida.";
            return new JsonModel($json);
        }

        $idca = (int)$_POST["categoria"];
        $categoria = $modeloCategoria->get($idca);

        if ( $categoria == NULL ) {
            $json["status"]="error";
            $json["msj"]="Debe seleccionar una categoría válida.";
            return new JsonModel($json);
        }
        try {
            $modeloCategoria->delete( $categoria["idca"] );
        } catch (\Exception $e) {
            $json["status"]="error";
            $json["msj"]="Ocurrió un error mientras se procesaba su solicitud, por favor inténtelo de nuevo más tarde.";
            return new JsonModel($json);
        }

        $json["status"]="ok";
        $json["msj"]="Categoría eliminada correctamente.";
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

        $this->dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloCategoria = new Modelocategorias($this->dbAdapter);
        
        $json=array();
        $autocomplete=array();
        $idr = null;
        if( (int)$identidad->tipo_user!=1 )
            $idr = $identidad->idr;
        
        $category_name=addslashes( trim($_POST["search"]) );
        $autocomplete_tmp=$modeloCategoria->all(0, 10, $category_name, 'ca.category_name ASC', null);//START, LIMIT, NAME

        //==============================================================
        //VALIDA QUE LA CATEGORIA SEA DEL USUARIO
        if ( $autocomplete_tmp!=NULL){
            foreach ($autocomplete_tmp as $value) {

                $autocomplete[]=array(
                    'nombre' => stripslashes($value["category_name"]),
                    'id'     => $value["idca"]
                );
            }
        }

        $json["status"]="ok";
        $json["msj"]="";
        $json["items"]=$autocomplete;
        return new JsonModel($json);
    }
}