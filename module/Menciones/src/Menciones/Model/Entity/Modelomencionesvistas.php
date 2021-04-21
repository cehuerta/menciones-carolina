<?php
namespace Menciones\Model\Entity;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Predicate;

class Modelomencionesvistas{

    public $dbAdapter;
    public $sql;
    public $table_name='menciones_vistas';

    public function __construct($adapter)
    {
        $this->dbAdapter=$adapter;
        $this->sql = new Sql($this->dbAdapter);
    }


    public function add($datos) 
    {
        $insert = $this->sql->insert();
        $insert->into($this->table_name);
        $insert->values($datos);
        $statement = $this->sql->prepareStatementForSqlObject($insert);
        $statement->execute();

        return $this->dbAdapter->getDriver()->getLastGeneratedValue();
    }


    public function all($start=0, $limit=20, $idm=null, $idsched=null, $id_user=null, $idr=null) 
    {
        $select = $this->sql->select();

        $exp_mention_view_date=new Expression('date_format(mv.mention_view_date,"%d.%m.%Y")');
        $exp_schedule_hour=new Expression('time_format(h.schedule_hour,"%H:%i")');

        $exp_mention_view_date_create=new Expression('date_format(mv.mention_view_date_create,"%d.%m.%Y - %H:%i")');

        $select->columns(array('*', 'mention_view_date_f'=>$exp_mention_view_date, 'schedule_hour_f'=>$exp_schedule_hour, 'mention_view_date_create_f'=>$exp_mention_view_date_create), 'mv');
        $select->from(array( 'mv'=>$this->table_name) );
        $select->join(array( 'u'=> 'usuarios'), 'mv.id_user=u.id_user', array('nombre_completo','correo') );
        $select->join(array( 'm'=> 'menciones'), 'mv.idm=m.idm', array('mention_description','mention_days') );
        $select->join(array('camp'=>'campanas'), 'm.idcamp=camp.idcamp', array('campaign_title'));
        $select->join(array('h'=>'horarios'), 'mv.idsched=h.idsched', array('schedule_hour'));


        //UTILIZADO PARA CALCULAR EL TOTAL DE RESULTADOS SEGUN LOS FILTROS
        $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS') );

        if($idm!==null)
            $select->where( array('mv.idm'=>$idm) );
        
        if($idsched!==null)
            $select->where( array('mv.idsched'=>$idsched) );

        if($id_user!==null)
            $select->where( array('mv.id_user'=>$id_user) );

        if($idr!==null)
            $select->where( array('camp.idr'=>$idr) );

        if($start!==null)
            $select->offset($start);
        if($limit!==null)
            $select->limit($limit);

        $select->order('mv.idmv DESC');

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();

        $resultSet = new ResultSet;
        $result=$resultSet->initialize($result);
        return $result->toArray();
    }

    public function found() 
    {
        //UTILIZADO PARA OBTENER EL TOTAL DE RESULTADOS SEGUN LA OBTENCION DE RESULTADOS DEL METODO "ALL"
        $select = $this->sql->select();
        $select->columns(array( new Expression('FOUND_ROWS() as total') ), '');
        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        $result=$result->current();
        return $result["total"];
    }

    public function get($idmv=null, $idm=null, $idsched=null, $id_user=null ) 
    {
        if($idmv===null and $idm===null and $idsched===null and $id_user===null )
            return null;

        $select = $this->sql->select();

        $exp_mention_view_date=new Expression('date_format(mv.mention_view_date,"%d.%m.%Y")');
        $select->columns(array('*', 'mention_view_date_f'=>$exp_mention_view_date ), 'mv');
        $select->from(array( 'mv'=>$this->table_name) );
        $select->join(array( 'u'=> 'usuarios'), 'mv.id_user=u.id_user',array('nombre_completo','correo') );

        if($idmv!==null)
            $select->where( array('mv.idmv'=>$idmv) );
        if($idm!==null)
            $select->where( array('mv.idm'=>$idm) );
        if($idsched!==null)
            $select->where( array('mv.idsched'=>$idsched) );
        if($id_user!==null)
            $select->where( array('mv.id_user'=>$id_user) );
        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        return $result->current();
    }


    public function delete($idmv=null, $idm=null, $idsched=null, $id_user=nul ) 
    {
        if($idmv===null and $idm===null and $idsched===null and $id_user===null )
            return null;

        $select = $this->sql->delete();
        $select->from($this->table_name);

        if($idmv!==null)
            $select->where( array('idmv'=>$idmv) );
        if($idm!==null)
            $select->where( array('idm'=>$idm) );
        if($idsched!==null)
            $select->where( array('idsched'=>$idsched) );
        if($id_user!==null)
            $select->where( array('id_user'=>$id_user) );

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
    }


    public function contCustom($idm=null, $idsched=null, $id_user=nul, $dia=null, $mention_status=null, $idr=null ) 
    {
        // SI TODOS LOS PARAMETROS ESTAN EN NULL, RETORNAMOS 0
        if($idm===null and $idsched===null and $id_user===null and $dia===null and $mention_status===null and $idr===null )
            return 0;

        $select = $this->sql->select();
        $select->columns(array( 'total'=> new Expression('count(*)' ) ), '');

        $select->from(array( 'mv'=>$this->table_name) );
        $select->join(array( 'm'=>'menciones'), 'mv.idm=m.idm', array() );

        if($idm!==null)
            $select->where( array('mv.idm'=>$idm) );
        if($idsched!==null)
            $select->where( array('mv.idsched'=>$idsched) );
        if($id_user!==null)
            $select->where( array('mv.id_user'=>$id_user) );
        if($dia!==null)
            $select->where( array('mv.mention_view_date'=>$dia) );
        if($mention_status!==null)
            $select->where( array('m.mention_status'=>$mention_status) );

        if($idr!==null){
            $select->join( array('c'=>'campanas'), 'm.idcamp=c.idcamp', array() );
            $select->where( array('c.idr'=>$idr) );
        }


        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        $result=$result->current();//
        return $result["total"];
    }

    public function contUnreadPreview($idm=null, $schedule_hour=null, $id_user=nul, $dia=null, $mention_status=null, $dia_num=null, $idr=null ) 
    {
        // SI TODOS LOS PARAMETROS ESTAN EN NULL, RETORNAMOS 0
        if($idm===null and $schedule_hour===null and $id_user===null and $dia===null and $mention_status===null and $dia_num===null and $idr===null )
            return 0;

        $select = $this->sql->select();
        $select->columns(array( 'total'=> new Expression('count(*)' ) ), '');
        $select->from(array( 'm'=>'menciones') );
        $select->join(array( 'h'=>'horarios'), 'm.idm=h.idm', array() );
        $select->join(array( 'mv'=>'menciones_vistas'), new Expression('m.idm=mv.idm AND h.idsched=mv.idsched AND mv.mention_view_date="'.$dia.'"'), array(), $select::JOIN_LEFT );

        if($idm!==null)
            $select->where( array('m.idm'=>$idm) );
        if($dia!==null)
            $select->where( array('m.mention_date_start<="'.$dia.'" AND m.mention_date_end>="'.$dia.'" ') );
        if($schedule_hour!==null)
            $select->where( array('h.schedule_hour<"'.$schedule_hour.'"') );
        if($id_user!==null)
            $select->where( array('m.id_user'=>$id_user) );
        if($mention_status!==null)
            $select->where( array('m.mention_status'=>$mention_status) );
        if($dia_num!==null)
            $select->where( array('m.mention_days LIKE "%'.$dia_num.'%"') );

        $select->where( array('mv.idmv IS NULL') );
        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        // var_dump($statement);
        // die;
        $result=$statement->execute();
        $result=$result->current();//
        return $result["total"];
    }

}


?>