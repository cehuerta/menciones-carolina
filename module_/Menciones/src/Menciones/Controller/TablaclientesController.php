<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Menciones\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

use Zend\Validator;
use Zend\I18n\Validator as I18nValidator;
use Zend\Db\Adapter\Adapter;
use Zend\Crypt\Password\Bcrypt;
use Zend\Authentication\Adapter\DbTable as AuthAdapter;

//Componentes de autenticación
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\Session as SessionStorage;
use Zend\Session\Container;

use Menciones\Model\Entity\Modeloclientes;

class TablaclientesController extends AbstractActionController
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



    public function listaAction()
    {
        $identidad=$this->validaSesion();
        if( $identidad==false and !$this->request->isXmlHttpRequest() ){
            return $this->redirect()->toRoute( 'login' );
        }
        else if( $identidad==false and $this->request->isXmlHttpRequest() ){
            $sEcho=1;
            if( isset($_GET['sEcho']) and !empty($_GET['sEcho']) ){
                $sEcho=abs($_GET["sEcho"]);
            }
            $output = array(
                "sEcho"                 => $sEcho,
                "iTotalRecords"         => 0,
                "iTotalDisplayRecords"  => 0,
                "aaData"                => array(),
            );
            return new JsonModel($output);
        }
        else if( !$this->request->isXmlHttpRequest()){
            return $this->redirect()->toRoute( 'login' );
        }

        $this->dbAdapter    = $this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloCliente      = new Modeloclientes($this->dbAdapter);

        $aColumns = array( 'client_name', 'cliente_date_create', 'version');
        
        
        /* 
         * Paging
         */
        $sLimit = null;
        $sStart = null;
        if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )
        {
            $sLimit=intval( $_GET['iDisplayLength'] );
            $sStart=intval( $_GET['iDisplayStart'] );
        }
        
        /*
         * Ordering
         */
        $sOrder = null;
        if ( isset( $_GET['iSortCol_0'] ) )
        {
            for ( $i=0 ; $i<intval( $_GET['iSortingCols'] ) ; $i++ )
            {
                if ( $_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true" )
                {
                    $sOrder = $aColumns[ intval( $_GET['iSortCol_'.$i] ) ].' '.addslashes( $_GET['sSortDir_'.$i] );
                }
            }
        }
        

        $sWhereLike = null;
        if ( isset($_GET['sSearch']) && $_GET['sSearch'] != "" )
        {
            if ( isset($_GET['bSearchable_'.$i]) && $_GET['bSearchable_'.$i] == "true" )
            {
                $sWhereLike=addslashes( $_GET['sSearch'] );
            }
        }
        
        try {
            $rResult = $modeloCliente->all( $sStart, $sLimit, null, $sOrder, $sWhereLike);
        } catch (\Exception $e) {
            $output = array(
                "sEcho"                 => abs($_GET["sEcho"]),
                "iTotalRecords"         => 0,
                "iTotalDisplayRecords"  => 0,
                "aaData"                => array(),
                "error"                 => (string)$e,
            );
            return new JsonModel($output);
        }

        try {
            $iFilteredTotal = abs($modeloCliente->found());
        } catch (\Exception $e) {
            $output = array(
                "sEcho"                 => abs($_GET["sEcho"]),
                "iTotalRecords"         => 0,
                "iTotalDisplayRecords"  => 0,
                "aaData"                => array(),
                "error"                 => (string)$e,
            );
            return new JsonModel($output);
        }

        $output = array(
            "sEcho"                 => intval($_GET['sEcho']),
            "iTotalRecords"         => count($rResult),
            "iTotalDisplayRecords"  => $iFilteredTotal,
            "aaData"                => array(),
        );


        
        $length=10;
        $page=1;
        $search='';
        $order=0;
        $order_dir='asc';
        if(isset($_GET["iDisplayLength"]) and !empty($_GET["iDisplayLength"]) and (int)$_GET["iDisplayLength"]>10){
            $length=(int)$_GET["iDisplayLength"];
        }
        if(isset($_GET['iDisplayStart']) and is_numeric($_GET['iDisplayStart']) and (int)$_GET['iDisplayStart']>0 ){
            $page=((int)$_GET["iDisplayStart"]/$length)+1; //page 0 => pagina 1 ...
        }
        if(isset($_GET["sSearch"]) and !empty($_GET["sSearch"])){
            $search=urlencode($_GET["sSearch"]); 
        }
        if( isset($_GET["iSortCol_0"]) and is_numeric($_GET["iSortCol_0"]) and (int)$_GET["iSortCol_0"]<=(int)(count($aColumns)-1) ){
            $order=abs($_GET["iSortCol_0"]); 
        }
        if( isset($_GET["sSortDir_0"]) and !empty($_GET["sSortDir_0"]) ){
            $order_dir=urlencode($_GET["sSortDir_0"]); 
        }
        $url_get="?cliente=".$search."&length=".$length."&page=".$page."&order=".$order.'&order_dir='.$order_dir;


        foreach ($rResult as $aRow) 
        {
            $row = array();
            $row["DT_RowId"]="row_".$aRow["idc"];

            for ( $i=0 ; $i<count($aColumns) ; $i++ )
            {
                if ( $aColumns[$i] == "version" )
                {
                    /* Special output formatting for 'version' column */
                    $var="<div class='btnsAccion'>";

                        $var.="<button class='btn btn-default btn-sm linkEditar' data-toggle='tooltip' data-placement='top' title='Editar' data='".$aRow["idc"]."'><span class='fa fa-wrench'></span></button>";
                        $var.="<button class='btn btn-danger btn-sm linkEliminar' data-toggle='tooltip' data-placement='top' title='Eliminar' data='".$aRow["idc"]."'><span class='fa fa-remove'></span></button>";

                    $var.="</div>";
                    $row["version"]=$var;
                }
                else if ( $aColumns[$i] == 'cliente_date_create' )
                {
                    $row["fecha"] = $aRow["cliente_date_create_fecha"].' | '.$aRow["cliente_date_create_hora"];
                }
                else if ( $aColumns[$i] == 'client_name' )
                {
                    //MOSTRAMOS TOTAL CAMPAÑAS
                    $extra_t_campanas="<br /><i class='fa fa-th-list'></i> Campañas: <span class='text-purple'>0</span>";
                    if( !empty($aRow["t_campanas"]))
                        $extra_t_campanas="<br /><i class='fa fa-th-list'></i> Campañas: <span class='text-purple'>".abs($aRow["t_campanas"]).'</span>';
                    
                    //MOSTRAMOS NOMBRE
                    $row["nombre"] = '<i class="fa fa-user" ></i> <span class="text-info text-semibold">'.stripslashes($aRow["client_name"]).'</span><br />'.$extra_t_campanas;
                }
                
            }
            $output['aaData'][] = $row;
        }
        
        return new JsonModel($output);
    }

}
