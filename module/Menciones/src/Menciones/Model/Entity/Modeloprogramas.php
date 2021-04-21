<?php
namespace Menciones\Model\Entity;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Predicate;

class Modeloprogramas{

    public $dbAdapter;
    public $sql;
    public $table_name='programas';

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

    public function all($start=0, $limit=10, $program_name=null, $order=null, $like=null) 
    {
        $select = $this->sql->select();

        $exp_fecha          = new Expression('date_format(pr.program_date_create, "%d.%m.%Y")');
        $exp_hora           = new Expression('time_format(pr.program_date_create, "%H:%i")');
        // $exp_t_campanas     = new Expression('(select count(*) from campanas where idc=c.idc)');

        $select->columns(array('*', 'program_date_create_fecha'=>$exp_fecha, 'program_date_create_hora'=>$exp_hora), 'pr');
        $select->from(array( 'pr'=>$this->table_name) );
        // $select->join(array( 'r'=>'radios'), 'c.idr=r.idr', array('radio_name'), $select::JOIN_LEFT );

        if($program_name!==null)
            $select->where( new Predicate\Like('pr.program_name', '%'.$program_name.'%' ) );

        //LIKE DESDE LA TABLA
        if($like!==null):
            $select->where( new Predicate\Like('pr.program_name', '%'.$like.'%' ) );
        endif;

        // if($idr!==null)
        //     $select->where( array('c.idr'=>$idr) );

        //UTILIZADO PARA CALCULAR EL TOTAL DE RESULTADOS SEGUN LOS FILTROS
        $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS') );

        if($start!==null)
            $select->offset($start);
        if($limit!==null)
            $select->limit($limit);

        if($order!==null)
            $select->order($order);

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

    public function get($idpro) 
    {
        $select = $this->sql->select();
        $exp_fecha=new Expression('date_format(program_date_create, "%d.%m.%Y")');
        $exp_hora=new Expression('time_format(program_date_create, "%H:%i")');

        $select->columns(array('*', 'program_date_create_fecha'=>$exp_fecha, 'program_date_create_hora'=>$exp_hora), '');
        $select->from(array( 'pr'=>$this->table_name) );

        $select->where(array('pr.idpro'=>$idpro) );
        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        return $result->current();
    }    

    public function update($datos, $idpro)
    {
        $update = $this->sql->update();
        $update->table($this->table_name);
        $update->set($datos);
        $update->where(array('idpro'=>$idpro) );
        $statement = $this->sql->prepareStatementForSqlObject($update);
        $statement->execute();
    }

    public function delete($idpro)
    {
        if($idpro==null){
            return false;
        }

        $select = $this->sql->delete();
        $select->from($this->table_name);
        $select->where(array('idpro'=>$idpro) );

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
    }

    public function contCustom($program_slug=null, $program_name=null) 
    {
        // SI TODOS LOS PARAMETROS ESTAN EN NULL, RETORNAMOS 0
        if($program_slug===null and $program_name===null)
            return 0;

        $select = $this->sql->select();
        $select->columns(array( 'total'=> new Expression('count(*)' ) ), '');

        $select->from($this->table_name);
        
        if($program_slug!==null)
            $select->where( array('program_slug'=>$program_slug) );

        if($program_name!=null)
            $select->where(array('program_name'=>$program_name) );

        // if($idr!=null)
        //     $select->where(array('idr'=>$idr) );
        
        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        $result=$result->current();//
        return $result["total"];
    }
}

?>