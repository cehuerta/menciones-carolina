<?php
namespace Menciones\Model\Entity;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Predicate;

class Modeloclientes{

    public $dbAdapter;
    public $sql;
    public $table_name='clientes';

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


    public function all($start=0, $limit=10, $client_name=null, $order=null, $like=null, $idr=null ) 
    {
        $select = $this->sql->select();

        $exp_fecha          = new Expression('date_format(c.cliente_date_create, "%d.%m.%Y")');
        $exp_hora           = new Expression('time_format(c.cliente_date_create, "%H:%i")');
        $exp_t_campanas     = new Expression('(select count(*) from campanas where idc=c.idc)');

        $select->columns(array('*', 'cliente_date_create_fecha'=>$exp_fecha, 'cliente_date_create_hora'=>$exp_hora, 't_campanas'=>$exp_t_campanas ), 'c');
        $select->from(array( 'c'=>$this->table_name) );
        $select->join(array( 'r'=>'radios'), 'c.idr=r.idr', array('radio_name'), $select::JOIN_LEFT );

        if($client_name!==null)
            $select->where( new Predicate\Like('c.client_name', '%'.$client_name.'%' ) );

        //LIKE DESDE LA TABLA
        if($like!==null):
            $select->where( new Predicate\Like('c.client_name', '%'.$like.'%' ) );
        endif;

        if($idr!==null)
            $select->where( array('c.idr'=>$idr) );

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

    public function get($idc) 
    {
        $select = $this->sql->select();
        $exp_fecha=new Expression('date_format(cliente_date_create, "%d.%m.%Y")');
        $exp_hora=new Expression('time_format(cliente_date_create, "%H:%i")');

        $select->columns(array('*', 'cliente_date_create_fecha'=>$exp_fecha, 'cliente_date_create_hora'=>$exp_hora), '');
        $select->from(array( 'c'=>$this->table_name) );


        $select->where(array('c.idc'=>$idc) );
        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        return $result->current();
    }
    

    public function update($datos, $idc)
    {
        $update = $this->sql->update();
        $update->table($this->table_name);
        $update->set($datos);
        $update->where(array('idc'=>$idc) );
        $statement = $this->sql->prepareStatementForSqlObject($update);
        $statement->execute();
    }


    public function delete($idc)
    {
        if($idc==null){
            return false;
        }

        $select = $this->sql->delete();
        $select->from($this->table_name);
        $select->where(array('idc'=>$idc) );

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
    }


    public function contCustom($client_slug=null, $client_name=null, $idr=null ) 
    {
        // SI TODOS LOS PARAMETROS ESTAN EN NULL, RETORNAMOS 0
        if($client_slug===null and $client_name===null)
            return 0;

        $select = $this->sql->select();
        $select->columns(array( 'total'=> new Expression('count(*)' ) ), '');

        $select->from($this->table_name);
        
        if($client_slug!==null)
            $select->where( array('client_slug'=>$client_slug) );

        if($client_name!=null)
            $select->where(array('client_name'=>$client_name) );

        if($idr!=null)
            $select->where(array('idr'=>$idr) );
        
        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        $result=$result->current();//
        return $result["total"];
    }
}


?>