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

use Menciones\Model\Entity\Modelomenciones;
use Application\Model\Entity\Modelofunctions;

class TablamencionesController extends AbstractActionController
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
        $modeloMencion      = new Modelomenciones($this->dbAdapter);
        $modeloFunction     = new Modelofunctions($this->dbAdapter);


        $aColumns = array('mention_description', 'mention_status', 'mention_date_create', 'version');
        
        if(!isset($_GET['campana']) or empty($_GET['campana']) or !is_numeric($_GET['campana']) or abs($_GET['campana'])<=0 ){
            $sEcho=1;
            if( isset($_GET['sEcho']) and !empty($_GET['sEcho']) ){
                $sEcho=abs($_GET["sEcho"]);
            }
            $output = array(
                "sEcho"                 => $sEcho,
                "iTotalRecords"         => 0,
                "iTotalDisplayRecords"  => 0,
                "aaData"                => array(),
                "error"                 => "La campaña no existe."
            );
            return new JsonModel($output);
        }
        $idcamp=abs($_GET['campana']);
        
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
            $rResult = $modeloMencion->all( $sStart, $sLimit, $idcamp, null, null, null, null, $sOrder, $sWhereLike);
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
            $iFilteredTotal = abs($modeloMencion->found());
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
        $url_get="?mencion=".$search."&length=".$length."&page=".$page."&order=".$order.'&order_dir='.$order_dir;

        foreach ($rResult as $aRow) 
        {
            $row = array();
            $row["DT_RowId"]="row_".$aRow["idm"];
            if($aRow["mention_status"]==0){
                $row["DT_RowClass"]="warning";
            }

            for ( $i=0 ; $i<count($aColumns) ; $i++ )
            {
                if ( $aColumns[$i] == "version" )
                {
                    /* Special output formatting for 'version' column */
                    $var="<div class='btnsAccion'>";
                        $var.="<button class='btn btn-default btn-sm linkEditar' data-toggle='tooltip' data-placement='top' title='Editar' data='".$aRow["idm"]."'><span class='glyphicon glyphicon-wrench'></span></button>";
                        $var.="<button class='btn btn-info btn-sm linkView' data-toggle='tooltip' data-placement='top' title='Ver' data='".$aRow["idm"]."'><span class='fa fa-eye'></span></button>";
                        if($aRow["mention_status"]==1){
                            $var.="<button class='btn btn-danger btn-sm linkStatus' data-toggle='tooltip' data-placement='top' title='Deshabilitar' data='".$aRow["idm"]."' data-status='habilitado'><i class='fa fa-ban'></i></button>";
                        }
                        else{
                            $var.="<button class='btn btn-success btn-sm linkStatus' data-toggle='tooltip' data-placement='top' title='Habilitar' data='".$aRow["idm"]."' data-status='deshabilitado'><i class='fa fa-check'></i></button>";
                        }
                        $var.="<button class='btn btn-danger btn-sm linkEliminar' data-toggle='tooltip' data-placement='top' title='Eliminar' data='".$aRow["idm"]."'><span class='glyphicon glyphicon-remove'></span></button>";

                    $var.="</div>";
                    $row["version"]=$var;
                }
                else if ( $aColumns[$i] == 'mention_date_create' )
                {
                    $row["fecha"] = $aRow["mention_date_create_fecha"].' | '.$aRow["mention_date_create_hora"];
                }
                else if ( $aColumns[$i] == 'mention_status' )
                {
                    $icon="<label class='label label-table label-danger'><i class='fa fa-ban '></i> <em class='text-white'>Deshabilitada</em></label>";
                    if($aRow["mention_status"]==1){
                        $icon="<label class='label label-table label-success'><i class='fa fa-check '></i> <em class='text-white'>Habilitada</em></label>";
                    }
                    $row["estado"]=$icon;
                }
                else if ( $aColumns[$i] == 'mention_description' )
                {
                    //MOSTRAMOS TOTAL DE HORARIOS
                    $extra_t_schedule="<br /><i class='fa fa-clock-o'></i> Total Horarios: 0";
                    if( !empty($aRow["t_horarios"]))
                        $extra_t_schedule="<br /><i class='fa fa-clock-o'></i> Total Horarios: <span class='text-purple'>".abs($aRow["t_horarios"]).'</span>';

                    //MOSTRAMOS TOTAL DE HORARIOS
                    $extra_schedule="<br /><i class='fa fa-clock-o'></i> Horarios: 0";
                    if( !empty($aRow["hora_string"]))
                        $extra_schedule="<br /><i class='fa fa-clock-o'></i> Horarios: <span class='text-purple'>".(string)$aRow["hora_string"].'</span>';

                    $extra_dias="<br /><i class='fa fa-sun-o'></i> Días: --";
                    if( !empty($aRow["mention_days"]))
                        $extra_dias="<br /><i class='fa fa-sun-o'></i> Días: <span class='text-purple'>".$modeloFunction->diasName(stripslashes($aRow["mention_days"])).'</span>';

                    //MOSTRAMOS FECHA INICIO
                    $extra_periodo="<br /><i class='fa fa-calendar'></i> Periodo: -- | -- ";
                    if( !empty($aRow["mention_date_start_f"]) and !empty($aRow["mention_date_end_f"]))
                        $extra_periodo="<br /><i class='fa fa-calendar'></i> Periodo: <span class='text-purple'>".stripslashes($aRow["mention_date_start_f"]).' | '.stripslashes($aRow["mention_date_end_f"]).'</span>';

                    $mention_description_ext=$modeloFunction->createExtracto(stripslashes($aRow["mention_description"]), 60);
                    //MOSTRAMOS NOMBRE
                    $row["extracto"] = '<i class="fa fa-commenting-o" ></i> <a href="#" class="btn-link text-semibold text-info linkView" data="'.$aRow["idm"].'">'.$mention_description_ext.'</a><br />'.$extra_t_schedule.$extra_schedule.$extra_dias.$extra_periodo;
                }
                
            }
            $output['aaData'][] = $row;
        }
        
        return new JsonModel($output);
    }



    public function calendarioAction()
    {
        $identidad=$this->validaSesion();
        $this->dbAdapter    = $this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloFunction     = new Modelofunctions($this->dbAdapter);
        $sLimit = "";
        $sOrder = " ";
        $sWhere="";


        $start='0000-00-00';
        $end='0000-00-00';
        if(isset($_GET["start"]) and !empty($_GET["start"]) ){
            $start=addslashes($_GET["start"]);
        }
        if(isset($_GET["end"]) and !empty($_GET["end"]) ){
            $end=addslashes($_GET["end"]);
        }
        
        if(!empty($sWhere)){
            $sWhere.=" AND m.mention_date_start>='".$start."' AND m.mention_date_end<='".$end."' ";
        }
        else{
            $sWhere.=" WHERE m.mention_date_start>='".$start."' AND m.mention_date_end<='".$end."' ";
        }
        $sWhere.=" AND m.mention_status=1 ";

        $sQuery = "
            SELECT SQL_CALC_FOUND_ROWS m.idm, m.mention_description, m.mention_days, m.mention_date_start, m.mention_date_end, m.mention_date_create, m.mention_status,
                date_format(m.mention_date_start, '%d.%m.%Y') AS mention_date_start_f,
                date_format(m.mention_date_end, '%d.%m.%Y') AS mention_date_end_f,
                date_format(m.mention_date_create, '%d.%m.%Y') AS mention_date_create_fecha,
                time_format(m.mention_date_create, '%H:%i') AS mention_date_create_hora,
                (select count(*) from horarios where idm=m.idm) AS t_horarios,
                c.campaign_title, c.idcamp, c.campaign_color
            FROM menciones AS m
            INNER JOIN campanas AS c ON m.idcamp=c.idcamp
            $sWhere
            $sOrder
            $sLimit
            ";
            // h.schedule_hour, h.idsched
            // INNER JOIN horarios AS h ON m.idm=h.idm
        $rResult=$this->dbAdapter->query($sQuery, Adapter::QUERY_MODE_EXECUTE);
        $rResult=$rResult->toArray();

        $sQuery = "
            SELECT FOUND_ROWS() as cont1
        ";
        $rResultFilterTotal=$this->dbAdapter->query($sQuery, Adapter::QUERY_MODE_EXECUTE);
        $aResultFilterTotal=$rResultFilterTotal->current();
        $iFilteredTotal = $aResultFilterTotal["cont1"];
        
        $agenda=array();
        foreach ($rResult as $value) {
            $old    ='false';
            $color  ='#5fa2dd';
            if( !empty($value['campaign_color']) )
                $color = stripslashes($value['campaign_color']);

            //SI EL EVENTO ES ANTERIOR AL DIA ACTUAL
            if( strtotime(date('Y-m-d')) >strtotime($value["mention_date_end"]) ){
                $old    ='true';
                $color  ='#c3cedb';
            }
            //SI EL EVENTO ESTA DESHABILITADO
            if( (int)$value["mention_status"]==0){
                $color  ='#f0ad4e';
            }
            $mention_description_ext=$modeloFunction->createExtracto( stripslashes($value["mention_description"]), 10);

            // // 'end'=>$value["event_date"].' '.$value["hora_fin_f"],
            $agenda[]=array(
                    'id'            => $value["idm"],
                    'title'         =>stripslashes($value["campaign_title"]),
                    'start'         =>$value["mention_date_start"],
                    'end'           =>$value["mention_date_end"],
                    'description'   =>$mention_description_ext.' - Horarios: '.abs($value["t_horarios"]),
                    'old'           =>$old,
                    'color'         =>$color
            );
        }
        return new JsonModel($agenda);
    }
}
