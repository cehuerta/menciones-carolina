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

use Menciones\Model\Entity\Modelopautas;

class TablapautasController extends AbstractActionController
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

        $this->dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloPauta     = new Modelopautas($this->dbAdapter);
        
        $aColumns        = array( 'title_pauta', 'fecha_pauta', 'version');
        $idr             = null;
        $sLimit          = null;
        $sStart          = null;
        $sOrder          = null;
        $search          = null;
        $dia_pauta       = null;
        $hora_pauta      = null;
        $pauta_leida     = null;

        if( isset($_GET['filtro_dia_pauta']) && !empty($_GET['filtro_dia_pauta']) && strlen($_GET['filtro_dia_pauta'])!=11 )
            $dia_pauta = (string)$_GET['filtro_dia_pauta'];

        if( $identidad->tipo_user!=1 ){
            $idr = $identidad->idr;
        }
        else{
            if( isset($_GET['radio']) && is_numeric($_GET['radio']) && abs($_GET['radio'])>0 )
                $idr = abs($_GET['radio']);
        }
        
        /* 
         * Paging
         */
        if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )
        {
            $sLimit=intval( $_GET['iDisplayLength'] );
            $sStart=intval( $_GET['iDisplayStart'] );
        }
        
        /*
         * Ordering
         */
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
        

        if ( isset($_GET['sSearch']) && $_GET['sSearch'] != "" )
        {
            if ( isset($_GET['bSearchable_'.$i]) && $_GET['bSearchable_'.$i] == "true" )
            {
                $search=addslashes( $_GET['sSearch'] );
            }
        }
        
        try {
            $arg_query                = [];
            $arg_query['idr']         = $idr;
            $arg_query['start']       = $sStart;
            $arg_query['limit']       = $sLimit;
            $arg_query['order']       = $sOrder;
            $arg_query['search']      = $search;
            $arg_query['dia_pauta']   = $dia_pauta;
            $arg_query['hora_pauta']  = $hora_pauta;
            $arg_query['pauta_leida'] = $pauta_leida;
            
            $rResult                  = $modeloPauta->all( $arg_query );
            $iFilteredTotal           = abs($modeloPauta->found());
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


        
        $length    = 10;
        $page      = 1;
        $search    = '';
        $order     = 0;
        $order_dir = 'asc';
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
        $url_get="?search=".$search."&length=".$length."&page=".$page."&order=".$order.'&order_dir='.$order_dir;


        foreach ($rResult as $aRow) 
        {
            $row = array();
            $row["DT_RowId"]="row_".$aRow["idpauta"];

            for ( $i=0 ; $i<count($aColumns) ; $i++ )
            {
                if ( $aColumns[$i] == "version" )
                {
                    /* Special output formatting for 'version' column */
                    $var="<div class='btnsAccion'>";

                        $var.="<button class='btn btn-default btn-sm linkEditar' data-toggle='tooltip' data-placement='top' title='Editar' data='".$aRow["idpauta"]."'><span class='fa fa-wrench'></span></button>";
                        $var.="<button class='btn btn-danger btn-sm linkEliminar' data-toggle='tooltip' data-placement='top' title='Eliminar' data='".$aRow["idpauta"]."'><span class='fa fa-remove'></span></button>";

                    $var.="</div>";
                    $row["version"]=$var;
                }
                else if ( $aColumns[$i] == 'fecha_pauta' )
                {
                    $row["fecha"] = $aRow["fecha_pauta_fecha"].' | '.$aRow["fecha_pauta_hora"];
                }
                else if ( $aColumns[$i] == 'title_pauta' )
                {
                    //MOSTRAMOS ESTADO LEIDA
                    $extra_leida="<br /><i class='fa fa-eye'></i> Leida: <span class='text-danger' title='No leída'><i class='fa fa-ban'></i></span>";
                    if( abs($aRow["pauta_leida"])==1 )
                        $extra_leida="<br /><i class='fa fa-eye'></i> Leida: <span class='text-success' title='Leída'><i class='fa fa-check'></i></span>";

                    //MOSTRAMOS DIA PAUTA
                    $extra_dia_pauta="<br /><i class='fa fa-calendar'></i> Día: <span class='text-purple'>--</span>";
                    if( !empty($aRow["dia_pauta"]))
                        $extra_dia_pauta="<br /><i class='fa fa-calendar'></i> Día: <span class='text-purple'>".$aRow["dia_pauta_f"].'</span>';

                    //MOSTRAMOS HORA PAUTA
                    $extra_hora_pauta="<br /><i class='fa fa-clock-o'></i> Hora: <span class='text-purple'>--</span>";
                    if( !empty($aRow["hora_pauta"]))
                        $extra_hora_pauta="<br /><i class='fa fa-clock-o'></i> Hora: <span class='text-purple'>".$aRow["hora_pauta_f"].'</span>';

                    //MOSTRAMOS RADIO
                    $extra_radio="<br /><i class='fa fa-microphone'></i> Radio: --";
                    if( !empty($aRow["radio_name"]))
                        $extra_radio="<br /><i class='fa fa-microphone'></i> Radio: <span class='text-purple'>".stripslashes($aRow["radio_name"]).'</span>';
                    
                    //MOSTRAMOS NOMBRE
                    $row["nombre"] = '<span class="text-info text-semibold">'.stripslashes($aRow["title_pauta"]).'</span><br />'.$extra_dia_pauta.$extra_hora_pauta.$extra_radio.$extra_leida;
                }
                
            }
            $output['aaData'][] = $row;
        }
        
        return new JsonModel($output);
    }

}
