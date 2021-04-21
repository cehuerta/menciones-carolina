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
use Menciones\Model\Entity\Modelopautas;
use Application\Model\Entity\Modelofunctions;

class TablalocutorController extends AbstractActionController
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


        $aColumns = array('mention_description', 'idmv', 'schedule_hour', 'version');
        $idr    = null;
        if( (int)$identidad->tipo_user!=1 )
            $idr = $identidad->idr;
        
        
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
        $sOrder = "ORDER BY schedule_hour ASC";
        // if ( isset( $_GET['iSortCol_0'] ) )
        // {
        //     $sOrder = "ORDER BY  ";
        //     for ( $i=0 ; $i<intval( $_GET['iSortingCols'] ) ; $i++ )
        //     {
        //         if ( $_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true" )
        //         {
        //             $sOrder .= "`".$aColumns[ intval( $_GET['iSortCol_'.$i] ) ]."` ".
        //                 addslashes( $_GET['sSortDir_'.$i] ) .", ";
        //         }
        //     }
            
        //     $sOrder = substr_replace( $sOrder, "", -2 );
        //     if ( $sOrder == "ORDER BY" )
        //     {
        //         $sOrder = "";
        //     }
        // }
        $i=2;

        $sWhere = null;
        if ( isset($_GET['sSearch']) && $_GET['sSearch'] != "" )
        {
            $sWhere = "WHERE (";
            if ( isset($_GET['bSearchable_'.$i]) && $_GET['bSearchable_'.$i] == "true" )
            {
                $sWhere .= " m.mention_description LIKE '%".addslashes( $_GET['sSearch'] )."%' OR ";
                $sWhere .= " c.campaign_title LIKE '%".addslashes( $_GET['sSearch'] )."%' ";
            }
            $sWhere .= ')';
        }

        $fecha_dia  =date('Y-m-d');
        $dia_num    =date('N');
        if(isset($_GET['dia']) and !empty($_GET['dia']) and strlen($_GET['dia'])<=10){
            $fecha_dia  =addslashes($_GET['dia']);
            $dia_num    =date('N', strtotime($fecha_dia));
            // echo $fecha_dia.' - '.$dia_num;
        }
        
        $id_user    = $identidad->id_user;

        if(!empty($sWhere)){
            $sWhere.=" AND m.mention_date_start<='".$fecha_dia."' AND m.mention_date_end>='".$fecha_dia."' ";
        }
        else{
            $sWhere.=" WHERE m.mention_date_start<='".$fecha_dia."' AND m.mention_date_end>='".$fecha_dia."' ";
        }

        $sWhere.=" AND m.mention_days LIKE '%".$dia_num."%' ";
        $sWhere.=" AND m.mention_status=1 ";
        
        if( !empty($idr) )
            $sWhere.=" AND c.idr='".$idr."' ";

        // (select idmv from menciones_vistas where idm=m.idm and idsched=h.idsched and id_user='".$id_user."' ) AS mencion_vista
        $sQuery = "
            SELECT SQL_CALC_FOUND_ROWS m.idm, m.mention_description, m.mention_days, m.mention_date_start, m.mention_date_end, m.mention_date_create, m.mention_status,
                date_format(m.mention_date_start, '%d.%m.%Y') AS mention_date_start_f,
                date_format(m.mention_date_end, '%d.%m.%Y') AS mention_date_end_f,
                date_format(m.mention_date_create, '%d.%m.%Y') AS mention_date_create_fecha,
                time_format(m.mention_date_create, '%H:%i') AS mention_date_create_hora,
                time_format(h.schedule_hour, '%H:%i') AS schedule_hour_f,
                (select count(*) from horarios where idm=m.idm) AS t_horarios,
                c.campaign_title, c.idcamp,
                h.schedule_hour, h.idsched,
                mv.idmv,
                cli.client_name
            FROM menciones AS m
            INNER JOIN campanas AS c ON m.idcamp=c.idcamp
            INNER JOIN horarios AS h ON m.idm=h.idm 
            INNER JOIN clientes AS cli ON c.idc=cli.idc 
            LEFT JOIN menciones_vistas AS mv ON mv.idm=m.idm and mv.idsched=h.idsched and mv.mention_view_date='".$fecha_dia."'
            $sWhere
            $sOrder
            $sLimit
            ";

        try {
            $rResult=$this->dbAdapter->query($sQuery, Adapter::QUERY_MODE_EXECUTE);
            $rResult=$rResult->toArray();
        } catch (\Exception $e) {
            $sEcho=1;
            if( isset($_GET['sEcho']) and !empty($_GET['sEcho']) ){
                $sEcho=abs($_GET["sEcho"]);
            }
            $output = array(
                "sEcho"                 => $sEcho,
                "iTotalRecords"         => 0,
                "iTotalDisplayRecords"  => 0,
                "aaData"                => array(),
                "error"                 => (string)$e,
            );
            return new JsonModel($output);
        }
        

        $sQuery = "
            SELECT FOUND_ROWS() as cont1
        ";
        $rResultFilterTotal=$this->dbAdapter->query($sQuery, Adapter::QUERY_MODE_EXECUTE);
        $aResultFilterTotal=$rResultFilterTotal->current();
        $iFilteredTotal = $aResultFilterTotal["cont1"];

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
            $row["DT_RowId"]="row_".$aRow["idm"].'_'.$aRow["idsched"];
            if($aRow["mention_status"]==0){
                $row["DT_RowClass"]="warning";
            }

            for ( $i=0 ; $i<count($aColumns) ; $i++ )
            {
                if ( $aColumns[$i] == "version" )
                {
                    /* Special output formatting for 'version' column */
                    $var="<div class='btnsAccion'>";
                        $data_status='leida';
                        if( empty($aRow["idmv"]) )
                            $data_status='';

                        $var.="<button class='btn btn-info btn-sm linkView' data-toggle='tooltip' data-placement='top' title='Ver mención' data='".$aRow["idm"]."' data-hora='".$aRow["idsched"]."' data-status='".$data_status."'><span class='fa fa-eye'></span></button>";
                        if( empty($aRow["idmv"]) ){
                            // $var.="<button class='btn btn-success btn-sm linkLeida' data-toggle='tooltip' data-placement='top' title='Marcar como leida' data-hora='".$aRow["idsched"]."' ><i class='fa fa-check'></i></button>";
                        }
                    $var.="</div>";
                    $row["version"]=$var;
                }
                else if ( $aColumns[$i] == 'schedule_hour' )
                {
                    $row["hora"] = "<span class='text-bold badge badge-primary'>".$aRow["schedule_hour_f"]."</span>";
                }
                else if ( $aColumns[$i] == 'idmv' )
                {
                    $icon="<label class='label label-table label-danger'><i class='fa fa-ban '></i> <em class='text-white'>SIN LEER</em></label>";
                    if( !empty($aRow["idmv"]) ){
                        $icon="<label class='label label-table label-success'><i class='fa fa-check '></i> <em class='text-white'>LEIDA</em></label>";
                    }
                    $row["estado"]=$icon;
                }
                else if ( $aColumns[$i] == 'mention_description' )
                {
                    //MOSTRAMOS CAMPAÑA
                    $extra_campana="<br /><i class='fa fa-th-list'></i> Campaña: -- ";
                    if( !empty($aRow["campaign_title"]) )
                        $extra_campana="<br /><i class='fa fa-th-list'></i> Campaña: <span class='text-purple text-semibold'>".stripslashes($aRow["campaign_title"]).'</span>';

                    //MOSTRAMOS CLIENTE
                    $extra_cliente="<br /><i class='fa fa-user'></i> Cliente: --  ";
                    if( !empty($aRow["client_name"]) )
                        $extra_cliente="<br /><i class='fa fa-user'></i> Cliente: <span class='text-purple text-semibold'>".stripslashes($aRow["client_name"]).'</span>';

                    //MOSTRAMOS TOTAL DE HORARIOS
                    $extra_t_schedule="<br /><i class='fa fa-clock-o'></i> Horarios: 0";
                    if( !empty($aRow["t_horarios"]))
                        $extra_t_schedule="<br /><i class='fa fa-clock-o'></i> Horarios: <span class='text-purple'>".abs($aRow["t_horarios"]).'</span>';

                    //MOSTRAMOS FECHA INICIO
                    $extra_periodo="<br /><i class='fa fa-calendar'></i> Periodo: -- | -- ";
                    if( !empty($aRow["mention_date_start_f"]) and !empty($aRow["mention_date_end_f"]))
                        $extra_periodo="<br /><i class='fa fa-calendar'></i> Periodo: <span class='text-purple'>".stripslashes($aRow["mention_date_start_f"]).' | '.stripslashes($aRow["mention_date_end_f"]).'</span>';

                    $mention_description    =stripslashes($aRow["mention_description"]);
                    $mention_description_ext=$modeloFunction->createExtracto($mention_description, 60);
                    //MOSTRAMOS NOMBRE
                    $row["extracto"] = '<i class="fa fa-commenting-o" ></i> <a href="#" class="btn-link text-semibold text-info linkPop" data="'.$aRow["idm"].'">'.$mention_description_ext.'</a><div class="js-content-mencion" style="display:none;">'.nl2br($mention_description).'</div><br />'.$extra_campana.$extra_cliente.$extra_t_schedule.$extra_periodo;
                }
                
            }
            $output['aaData'][] = $row;
        }
        
        return new JsonModel($output);
    }


    public function listapautasAction()
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
        $modeloFunction  = new Modelofunctions($this->dbAdapter);
        $filtro_args     = [];
        $search          = null;
        $limit           = null;
        $start           = null;
        $idr             = null;
        $dia_pauta       = date('Y-m-d');
        $hora_pauta      = null;
        $pauta_leida     = null;
        $order           = null;
        $registros_tmp   = [];
        $aColumns        = array('title_pauta', 'pauta_leida', 'hora_pauta');
        /* Indexed column (used for fast and accurate table cardinality) */
        $sIndexColumn    = "idpauta";
        
        /* DB table to use */
        $alias_tabla     = "pa.";

        if( (int)$identidad->tipo_user!=1 )
            $idr = $identidad->idr;
        
        
        //**********************************************
        // FILTROS
        if(isset($_GET['dia']) and !empty($_GET['dia']) and strlen($_GET['dia'])<=10){
            $dia_pauta = addslashes($_GET['dia']);
            // $dia_pauta = new \DateTime::createFromFormat('d-m-Y', $dia_pauta);
            $dia_pauta = new \DateTime($dia_pauta);
            $dia_pauta = (string)$dia_pauta->format("Y-m-d");
            // echo $dia_pauta;
        }
        //**********************************************

        
        // ******************************
        // BUSCADOR
        if ( isset($_GET['sSearch']) && $_GET['sSearch'] != "" )
        {
            if ( isset($_GET['bSearchable_'.$i]) && $_GET['bSearchable_'.$i] == "true" )
            {
                $search = addslashes( $_GET['sSearch'] );
            }
        }
        // ******************************


        /* 
         * Paging
         */
        
        if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )
        {
            $limit = intval( $_GET['iDisplayLength'] );
            $start = intval( $_GET['iDisplayStart'] );
        }
        

        /*
         * Ordering
        */
        $order = 'pa.hora_pauta ASC';
        if ( isset( $_GET['iSortCol_0'] ) )
        {
            for ( $i=0 ; $i<intval( $_GET['iSortingCols'] ) ; $i++ )
            {
                if ( $_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true" )
                {
                    $order = $alias_tabla.$aColumns[ intval( $_GET['iSortCol_'.$i] ) ].' '.addslashes( $_GET['sSortDir_'.$i] );
                }
            }
        }


        /*
         * QUERY ALL
        */
        try {
            
            $args_query                = array();
            $args_query['limit']       = $limit;
            $args_query['start']       = $start;
            $args_query['order']       = $order;
            $args_query['search']      = $search;
            $args_query['idr']         = $idr;
            $args_query['dia_pauta']   = $dia_pauta;
            $args_query['hora_pauta']  = $hora_pauta;
            $args_query['pauta_leida'] = $pauta_leida;
            $registros_tmp             = $modeloPauta->all( $args_query );
            $t_found                   = abs($modeloPauta->found());
            
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
        
        

        //**************************************************************************************************************
        $length    = 10;
        $page      = 1;
        $search    = '';
        $order     = 0;
        $order_dir = 'asc';
        if(isset($_GET["iDisplayLength"]) and !empty($_GET["iDisplayLength"]) and (int)$_GET["iDisplayLength"]>10)
            $length=(int)$_GET["iDisplayLength"];
        
        if(isset($_GET['iDisplayStart']) and is_numeric($_GET['iDisplayStart']) and (int)$_GET['iDisplayStart']>0 )
            $page=((int)$_GET["iDisplayStart"]/$length)+1; //page 0 => pagina 1 ...
        
        if(isset($_GET["sSearch"]) and !empty($_GET["sSearch"]))
            $search=urlencode($_GET["sSearch"]); 
        
        if( isset($_GET["iSortCol_0"]) and is_numeric($_GET["iSortCol_0"]) and (int)$_GET["iSortCol_0"]<=(int)(count($aColumns)-1) )
            $order=abs($_GET["iSortCol_0"]); 
        
        if( isset($_GET["sSortDir_0"]) and !empty($_GET["sSortDir_0"]) )
            $order_dir=urlencode($_GET["sSortDir_0"]); 
        
        $url_get="?search=".$search."&length=".$length."&page=".$page."&order=".$order.'&order_dir='.$order_dir;
        //**************************************************************************************************************
        

        $registros = [];
        if($registros_tmp!=false ):

            foreach ($registros_tmp as $aRow) 
            {

                //**********************************************
                // ROW DATA
                $DT_RowId    = "row_".(string)$aRow["idpauta"];
                $DT_RowClass = '';
                // if( (int)$aRow["idpauta"]===0){
                //     $DT_RowClass = "warning";
                // }
                //**********************************************

                $data_status = 'leida';
                if( (int)$aRow["pauta_leida"]==0 )
                    $data_status = '';
                

                //**********************************************                
                // BOTONES
                $botones="<div class='btnsAccion'>";

                    $botones.="<button class='btn btn-info btn-sm linkViewPauta' data-toggle='tooltip' data-placement='top' title='Ver pauta' data-id='".$aRow["idpauta"]."' data-status='".$data_status."'><span class='fa fa-eye'></span></button>";

                $botones.="</div>";
                //**********************************************


                //**********************************************
                // ESTADO
                $pauta_leida = "<label class='label label-table label-danger'><i class='fa fa-ban '></i> <em class='text-white'>SIN LEER</em></label>";
                if( (int)$aRow["pauta_leida"]==1 )
                    $pauta_leida = "<label class='label label-table label-success'><i class='fa fa-check '></i> <em class='text-white'>LEIDA</em></label>";
                //**********************************************


                // EMAIL
                $hora_pauta = "<span class='text-bold badge badge-primary'>".$aRow["hora_pauta_f"]."</span>";


                // NOMBRE
                $title_pauta = '<label class="text-primary" >'.stripslashes($aRow['title_pauta']).'</label>';


                //**********************************************
                // ARRAY DATA RETORNO
                $registros[] = [
                    'DT_RowId'    => $DT_RowId,
                    'DT_RowClass' => $DT_RowClass,
                    'version'     => $botones,
                    'hora'        => $hora_pauta,
                    'estado'      => $pauta_leida,
                    'extracto'    => $title_pauta,
                ];
                //**********************************************
                
            }

        endif;


        // RETORNO DE DATOS
        $output = [
            "sEcho"                 => intval($_GET['sEcho']),
            "iTotalRecords"         => count($registros),
            "iTotalDisplayRecords"  => $t_found,
            "aaData"                => $registros,
        ];
        return new JsonModel($output);

    }
}
