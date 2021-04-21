<?php
namespace Application\Model\Entity;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Predicate;

class Modelodashboard{


    public $dbAdapter;
    public $sql;

    public function __construct($adapter)
    {
        $this->dbAdapter=$adapter;
        $this->sql = new Sql($this->dbAdapter);
    }

    public function totalMenciones($dia=null, $dia_num=null, $mention_status=null, $idr=null ){

        $select = $this->sql->select();
        $select->columns(array( 'total'=>new Expression('COUNT(*)') ), 'm');
        $select->from( array('m'=>'menciones') );
        $select->join( array('h'=>'horarios'), 'm.idm=h.idm', array() );

        if($dia!==null)
            $select->where('m.mention_date_start<="'.$dia.'" AND m.mention_date_end>="'.$dia.'" ');
        if($dia_num!==null)
            $select->where( new Predicate\Like('m.mention_days', '%'.$dia_num.'%' ) );
        if($mention_status!==null)
            $select->where(array('m.mention_status'=>$mention_status));

        if($idr!==null){
            $select->join( array('c'=>'campanas'), 'm.idcamp=c.idcamp', array() );
            $select->where( array('c.idr'=>$idr) );
        }
        
        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        $result=$result->current();
        return $result["total"];
    }

    public function totalCampanas($campaign_status=null, $idr=null ){

        $select = $this->sql->select();
        $select->columns(array( 'total'=>new Expression('COUNT(*)') ), '');
        $select->from( 'campanas' );

        if($campaign_status!==null)
            $select->where(array('campaign_status'=>$campaign_status));

        if($idr!==null)
            $select->where( array('idr'=>$idr) );
        

        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        $result=$result->current();
        return $result["total"];
    }

    public function totalClientes( $idr=null ){

        $select = $this->sql->select();
        $select->columns(array( 'total'=>new Expression('COUNT(*)') ), '');
        $select->from( 'clientes' );

        if($idr!==null)
            $select->where( array('idr'=>$idr) );
        

        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        $result=$result->current();
        return $result["total"];
    }

    public function totalUsuarios($tipo_user=null, $idr=null ){

        $select = $this->sql->select();
        $select->columns(array( 'total'=>new Expression('COUNT(*)') ), '');
        $select->from( 'usuarios' );

        if($tipo_user!==null)
            $select->where(array('tipo_user'=>$tipo_user));

        if($idr!==null)
            $select->where( array('idr'=>$idr) );
        

        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        $result=$result->current();
        return $result["total"];
    }

    public function userMayorRead($idr=null){

        $select = $this->sql->select();
        $select->columns(array( 'id_user', 'total'=>new Expression('COUNT(*)') ), 'mv');
        $select->from( array('mv'=>'menciones_vistas') );
        $select->join( array('u'=>'usuarios'), 'mv.id_user=u.id_user', array('nombre_completo', 'correo') );
        
        $select->having('total>0');
        $select->group('mv.id_user');

        if($idr!==null)
            $select->where( array('u.idr'=>$idr) );
        
        $select->order('total DESC');

        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        $result=$result->current();
        return $result;
    }


    public function readLastDays($id_user=null, $idr=null ){

        $select = $this->sql->select();

        $exp_count=new Expression('COUNT(*)');

        $select->columns( array( 'total'=>$exp_count ), 'u');
        $select->from( array('mv'=>'menciones_vistas') );
        $select->join( array('u'=>'usuarios'), 'mv.id_user=u.id_user', array() );


        $select->where('mv.mention_view_date >= DATE_ADD(NOW(), INTERVAL -7 DAY)');

        if($id_user!==null)
            $select->where(array('mv.id_user'=>$id_user));

        if($idr!==null)
            $select->where(array('u.idr'=>$idr));
        
        $select->having('total>0');
        $select->group( new Expression('date_format(mv.mention_view_date,"%d-%m-%Y")') );
        $select->limit(10);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();

        $resultSet = new resultSet;
        $result=$resultSet->initialize($result);
        return $result->toArray();
    }

}


?>