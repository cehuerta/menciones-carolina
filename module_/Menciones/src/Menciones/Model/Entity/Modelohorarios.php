<?php
namespace Menciones\Model\Entity;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Predicate;

class Modelohorarios{

    public $dbAdapter;
    public $sql;
    public $table_name='horarios';

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


    public function all($start=0, $limit=20, $idm=null, $schedule_hour=null) 
    {
        $select = $this->sql->select();

        $exp_schedule_hour   = new Expression('time_format(h.schedule_hour, "%H:%i")');

        $select->columns(array('*', 'schedule_hour_f'=>$exp_schedule_hour ), 'h');
        $select->from(array( 'h'=>$this->table_name) );

        //UTILIZADO PARA CALCULAR EL TOTAL DE RESULTADOS SEGUN LOS FILTROS
        $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS') );

        if($idm!==null)
            $select->where( array('h.idm'=>$idm) );
        if($schedule_hour!==null)
            $select->where( new Predicate\Like('h.schedule_hour', '%'.$schedule_hour.'%' ) );

        if($start!==null)
            $select->offset($start);
        if($limit!==null)
            $select->limit($limit);

        $select->order('schedule_hour ASC');

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

    public function get($idsched) 
    {
        $select = $this->sql->select();

        $exp_schedule_hour   = new Expression('time_format(h.schedule_hour, "%H:%i")');

        $select->columns(array('*', 'schedule_hour_f'=>$exp_schedule_hour ), 'h');
        $select->from(array( 'h'=>$this->table_name) );

        $select->where(array('h.idsched'=>$idsched) );
        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        return $result->current();
    }

    public function update($datos, $idsched)
    {
        $update = $this->sql->update();
        $update->table($this->table_name);
        $update->set($datos);
        $update->where(array('idsched'=>$idsched) );
        $statement = $this->sql->prepareStatementForSqlObject($update);
        $statement->execute();
    }

    public function delete($idsched=null, $idm=null)
    {
        if($idsched===null and $idm===null){
            return false;
        }

        $select = $this->sql->delete();
        $select->from($this->table_name);
        if($idsched!==null)
            $select->where(array('idsched'=>$idsched) );
        if($idm!==null)
            $select->where(array('idm'=>$idm) );

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
    }


    public function contCustom($idm=null, $schedule_hour=null) 
    {
        // SI TODOS LOS PARAMETROS ESTAN EN NULL, RETORNAMOS 0
        if($schedule_hour===null and $idm===null )
            return 0;

        $select = $this->sql->select();
        $select->columns(array( 'total'=> new Expression('count(*)' ) ), '');

        $select->from($this->table_name);

        if($idm!==null)
            $select->where( array('idm'=>$idm) );
        if($schedule_hour!=null)
            $select->where(array('schedule_hour'=>$schedule_hour) );
        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        $result=$result->current();//
        return $result["total"];
    }
}


?>