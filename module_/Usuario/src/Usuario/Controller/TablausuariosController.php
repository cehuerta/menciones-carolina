<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Usuario\Controller;

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

//componentes para enviar un correo
use Zend\Mail;
use Zend\Mail\Message;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Message as MimeMessage;

//modelos
use Usuario\Model\Entity\Modelopersonal;

class TablausuariosController extends AbstractActionController
{
	public $dbAdapter;
    private $auth;
    public $rutas;

    // public $rutaSave;

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




    //LISTA DE OPERADORAS PARA MOSTRAR EN VER CAMPAÑA
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

        $this->dbAdapter=$this->getServiceLocator()->get('Zend\Db\Adapter');

        //===================================================================
        $rutaUser=$this->rutas["rutas"]["rutaUser"];
        //===================================================================

        $aColumns = array( 'id_user', 'logo', 'nombre_completo', 'correo', 'tipo_user', 'fecha' ,'version');
        /* Indexed column (used for fast and accurate table cardinality) */
        $sIndexColumn = "id_user";
        
        /* DB table to use */
        $sTable = "usuarios";
        

        /* 
         * Paging
         */
        $sLimit = "";
        if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )
        {
            $sLimit = "LIMIT ".intval( $_GET['iDisplayStart'] ).", ".
                intval( $_GET['iDisplayLength'] );
        }
        
        /*
         * Ordering
         */
        $sOrder = "";
        if ( isset( $_GET['iSortCol_0'] ) )
        {
            $sOrder = "ORDER BY  ";
            for ( $i=0 ; $i<intval( $_GET['iSortingCols'] ) ; $i++ )
            {
                if ( $_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true" )
                {
                    $sOrder .= "`".$aColumns[ intval( $_GET['iSortCol_'.$i] ) ]."` ".
                        addslashes( $_GET['sSortDir_'.$i] ) .", ";
                }
            }
            
            $sOrder = substr_replace( $sOrder, "", -2 );
            if ( $sOrder == "ORDER BY" )
            {
                $sOrder = "";
            }
        }
        

        $sWhere = "";
        if ( isset($_GET['sSearch']) && $_GET['sSearch'] != "" )
        {
            $sWhere = "WHERE (";
            
            if ( isset($_GET['bSearchable_'.$i]) && $_GET['bSearchable_'.$i] == "true" )
            {
                $sWhere .= " u.nombre_completo LIKE '%".addslashes( $_GET['sSearch'] )."%' OR ";
                $sWhere .= " date_format(u.fecha_registro_user, '%d-%m-%Y | %H:%i:%s') LIKE '%".addslashes( $_GET['sSearch'] )."%' OR ";
                $sWhere .= " u.correo LIKE '%".addslashes( $_GET['sSearch'] )."%' OR ";
                $sWhere .= " CASE u.tipo_user 
                                WHEN 0 THEN 'Super Administrador' 
                                WHEN 1 THEN 'Usuario' 
                                WHEN 2 THEN 'Administrador'
                                END  LIKE '%".addslashes( $_GET['sSearch'] )."%' ";
            }
            $sWhere .= ')';
        }
        
        for ( $i=0 ; $i<count($aColumns) ; $i++ )
        {
            if ( isset($_GET['bSearchable_'.$i]) && $_GET['bSearchable_'.$i] == "true" && $_GET['sSearch_'.$i] != '' )
            {
                if ( $sWhere == "" )
                {
                    $sWhere = "WHERE ";
                }
                else
                {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`".$aColumns[$i]."` LIKE '%".addslashes($_GET['sSearch_'.$i])."%' ";
            }
        }

        $sQuery = "
            SELECT SQL_CALC_FOUND_ROWS u.id_user, u.nombre_completo, u.correo, u.tipo_user, u.slug, u.logo,
            date_format(u.fecha_registro_user, '%d-%m-%Y | %H:%i:%s') as fecha, up.name_profile as perfil, u.status
            FROM usuarios u
            INNER JOIN usuarios_profiles up ON u.tipo_user=up.idprof
            $sWhere
            $sOrder
            $sLimit
            ";
        $rResult=$this->dbAdapter->query($sQuery, Adapter::QUERY_MODE_EXECUTE);
        $rResult=$rResult->toArray();


        $sQuery = "
            SELECT FOUND_ROWS() as cont1
        ";
        $rResultFilterTotal=$this->dbAdapter->query($sQuery, Adapter::QUERY_MODE_EXECUTE);
        $aResultFilterTotal=$rResultFilterTotal->current();
        $iFilteredTotal = $aResultFilterTotal["cont1"];

        
        $output = array(
            "sEcho" => intval($_GET['sEcho']),
            "iTotalRecords" => count($rResult),
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array(),
        );


        $length=10;
        $page=1;
        $search='';
        if(isset($_GET["iDisplayLength"]) and !empty($_GET["iDisplayLength"]) and (int)$_GET["iDisplayLength"]>10){
            $length=(int)$_GET["iDisplayLength"];
        }
        if(isset($_GET["page"]) and !empty($_GET["page"]) and (int)$_GET["page"]>=1){
            $page=((int)$_GET["page"])+1; //page 0 => pagina 1 ...
        }
        if(isset($_GET["sSearch"]) and !empty($_GET["sSearch"])){
            $search=urlencode($_GET["sSearch"]); 
        }

        foreach ($rResult as $aRow) 
        {
            $row = array();
            $row["DT_RowId"]="row_".$aRow["id_user"];
            if($aRow["status"]==0){
                $row["DT_RowClass"]="warning";
            }
            for ( $i=0 ; $i<count($aColumns) ; $i++ )
            {
                if ( $aColumns[$i] == "version" )
                {

                    $rutaEditar=$this->url()->fromRoute('editaradm', array('id'=>$aRow['id_user']) );
                    $var="<div class='btnsAccion'>";
                            $var.="<a data-toggle='tooltip' data-placement='top' title='Perfil' class='btn btn-default btn-sm' href='".$rutaEditar."?user=".$search."&length=".$length."&page=".$page."'><i class='fa fa-user'></i></a>";


                        if($aRow["id_user"]!=$identidad->id_user){
                            if($aRow["status"]==0){//deshabilitado
                                $var.="<button data-toggle='tooltip' data-placement='top' title='Habilitar' class='btn btn-success btn-sm linkStatus' data='".$aRow['id_user']."' data-status='deshabilitado' ><i class='fa fa-check'></i></button>";
                            }
                            else{
                                $var.="<button data-toggle='tooltip' data-placement='top' title='Deshabilitar' class='btn btn-danger btn-sm linkStatus' data='".$aRow['id_user']."' data-status='habilitado' ><i class='fa fa-ban'></i></button>";
                            }
                        }

                        if( $identidad->tipo_user==1 and $aRow["id_user"]!=$identidad->id_user ){
                            $var.="<button data-toggle='tooltip' data-placement='top' title='Eliminar' class='btn btn-danger btn-sm linkEliminar' data='".$aRow['id_user']."' ><i class='fa fa-remove'></i></button>";
                        }

                    $var.="</div>";

                    $row["version"]=$var;
                }
                else if ( $aColumns[$i] == 'tipo_user' )
                {
                    $row["tipo"]= $aRow["perfil"];
                }
                else if ( $aColumns[$i] == 'correo' )
                {
                    $row["correo"]= stripslashes($aRow['correo']);
                }
                else if ( $aColumns[$i] == 'fecha' )
                {
                    $row["fecha"]= $aRow['fecha'];
                }
                else if ( $aColumns[$i] == 'logo' )
                {
                    if( empty($aRow['logo']) ){
                        $row["imagen"]="<img class='lazy img-responsive img-border img-circle' style='width: 100px;' data-original='".$this->getRequest()->getBaseUrl()."/public/img/".$this->rutas['NoImage']."' />";
                    }
                    else{
                        $row["imagen"]= "<img class='lazy img-responsive img-border img-circle' style='width: 100px;' data-original='".$rutaUser."/".$aRow['slug']."/".$aRow['logo']."' />";
                    }
                }
                else if( $aColumns[$i] == 'nombre_completo' )
                {
                    $row["nombre"]=stripslashes($aRow['nombre_completo']);
                }
                else if ( $aColumns[$i] == 'id_user' )
                {
                    $row["id"] = $aRow["id_user"];
                }
            }
            $output['aaData'][] = $row;
        }
        
        return new JsonModel($output);
    
    }

}
