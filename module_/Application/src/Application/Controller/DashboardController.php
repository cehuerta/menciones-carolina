<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

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

use Usuario\Model\Entity\Modelopersonal;
use Application\Model\Entity\Modelodashboard;
use Application\Model\Entity\Modelofunctions;

use Menciones\Model\Entity\Modelomenciones;
use Menciones\Model\Entity\Modelocampanas;
use Menciones\Model\Entity\Modelohorarios;
use Menciones\Model\Entity\Modelomencionesvistas;

class DashboardController extends AbstractActionController
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
            $this->layout()->nameUser   = $identi->nombre_completo;
            $this->layout()->type       = $identi->tipo_user;

        }else{
            return false;
        }

        return $identi;
    }


    public function getSymbolByQuantity($bytes) {
        $symbols = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB');
        $exp = floor(log($bytes)/log(1024));
        return round( ($bytes/pow(1024, floor($exp) )) ).$symbols[$exp];
    }

    public function dashboardAction()
    {
    	$identidad=$this->validaSesion();
        if($identidad==false)
            $this->redirect()->toRoute( 'login' );

        $this->dbAdapter    = $this->getServiceLocator()->get('Zend\Db\Adapter');
        $modeloDashboard    = new Modelodashboard($this->dbAdapter);
        $modeloCampana      = new Modelocampanas($this->dbAdapter);
        $modeloMencionVista = new Modelomencionesvistas($this->dbAdapter);
        $modeloMenciones    = new Modelomenciones($this->dbAdapter);
        $modeloFunction     = new Modelofunctions($this->dbAdapter);

        
        $total_bytes=disk_total_space("/");
        $total_free_bytes=disk_free_space("/");
        $diferencia_bytes=floatval($total_bytes-$total_free_bytes);
        $disk_usado=$this->getSymbolByQuantity( $diferencia_bytes );
        $disk_free=$this->getSymbolByQuantity( $total_free_bytes );

        $dia            = date('Y-m-d');
        $dia_num        = date('N');
        $id_user        = null;
        if($identidad->tipo_user==3)
            $id_user    = $identidad->id_user;

        $t_leidas_dia   = $modeloMencionVista->contCustom(null, null, null, $dia, 1 );
        $t_menciones_dia= $modeloDashboard->totalMenciones($dia, $dia_num, 1 );
        $t_no_leidas_dia= (int)$t_menciones_dia-$t_leidas_dia;

        $t_menciones    = $modeloDashboard->totalMenciones(null, null, 1 );
        $t_campanas     = $modeloDashboard->totalCampanas( 1 );
        $t_clientes     = $modeloDashboard->totalClientes( );
        $t_locutores    = $modeloDashboard->totalUsuarios( 3 );
        $readLastDays   = $modeloDashboard->readLastDays( $id_user );

        //ULTIMAS MENCIONES CREADAS
        $ultimas_menciones      = array();
        $ultimas_menciones_tmp  = $modeloMenciones->all(0,10, null, null, 1, null, null, 'm.idm DESC', null);
        if(is_array($ultimas_menciones_tmp) and count($ultimas_menciones_tmp)>0):
            foreach($ultimas_menciones_tmp AS $value ):
                $ultimas_menciones[]=array(
                    'titulo_campana'        => $value['campaign_title'],
                    'extracto_mencion'      => $modeloFunction->createExtracto(stripslashes($value["mention_description"]), 60),
                    'dias_mencion'          => $modeloFunction->diasName(stripslashes($value["mention_days"])),
                    'id'                    => $value['idm'],
                );
            endforeach;
        endif;

        // ULTIMAS MENCIONES LEIDAS
        $ultimas_menciones_leidas      = array();
        $ultimas_menciones_leidas_tmp  = $modeloMencionVista->all(0,10, null, null, $id_user);
        if(is_array($ultimas_menciones_leidas_tmp) and count($ultimas_menciones_leidas_tmp)>0):
            foreach($ultimas_menciones_leidas_tmp AS $value ):
                $ultimas_menciones_leidas[]=array(
                    'titulo_campana'        => $value['campaign_title'],
                    'extracto_mencion'      => $modeloFunction->createExtracto(stripslashes($value["mention_description"]), 60),
                    'dias_mencion'          => $modeloFunction->diasName(stripslashes($value["mention_days"])),
                    'fecha'                 => $value['mention_view_date_f'],
                    'hora'                  => $value['schedule_hour_f'],
                    'leida'                 => $value['mention_view_date_create_f'],
                    'id'                    => $value['idm'],
                );
            endforeach;
        endif;

        return new ViewModel(array(
            'disk_usado'                => $disk_usado,
            'disk_free'                 => $disk_free,
            't_menciones'               => $t_menciones,
            't_leidas_dia'              => $t_leidas_dia,
            't_menciones_dia'           => $t_menciones_dia,
            't_no_leidas_dia'           => $t_no_leidas_dia,
            't_campanas'                => $t_campanas,
            't_clientes'                => $t_clientes,
            't_locutores'               => $t_locutores,
            'readLastDays'              => $readLastDays,
            'ultimas_menciones'         => $ultimas_menciones,
            'ultimas_menciones_leidas'  => $ultimas_menciones_leidas
        ));
    }


    public function conectadosAction()
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
        $modeloDashboard=new Modelodashboard($this->dbAdapter);
        
        // $json=array();
        // $conectados=$modeloDashboard->actividadConectados('dia');
        
        // $data_return=array();
        // $total=0;
        // $total_a=0;
        // $total_ios=0;
        // foreach ($conectados as $key => $value) {
        //     $data_return[]=array(
        //         'elapsed'       =>$value["fecha_label"],
        //         'value_android'         =>$value["total_android"],
        //         'value_ios'             =>$value["total_ios"],
        //     );
        //     $total=(int)$total+ ( (int)$value["total_android"] + (int)$value["total_ios"] );//total entre ios y android
        //     $total_a=(int)$total_a + (int)$value["total_android"];//total android
        //     $total_ios=(int)$total_ios + (int)$value["total_ios"];//total ios
        // }

        $json["status"]='ok';
        $json["msj"]='';
        // $json["total"]=$total;
        // $json["total_a"]=$total_a;
        // $json["total_ios"]=$total_ios;
        // $json["data_return"]=$data_return;
        return new JsonModel($json);
    }

}
