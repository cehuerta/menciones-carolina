<?php
namespace Menciones\Model\Entity;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Predicate;

class Modelocategorias{

    public $dbAdapter;
    public $sql;
    public $table_name='categorias';

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

    public function all($start=0, $limit=10, $category_name=null, $order=null, $like=null) 
    {
        $select = $this->sql->select();

        $exp_fecha          = new Expression('date_format(ca.category_date_create, "%d.%m.%Y")');
        $exp_hora           = new Expression('time_format(ca.category_date_create, "%H:%i")');
        // $exp_t_campanas     = new Expression('(select count(*) from campanas where idc=c.idc)');

        $select->columns(array('*', 'category_date_create_fecha'=>$exp_fecha, 'category_date_create_hora'=>$exp_hora), 'ca');
        $select->from(array( 'ca'=>$this->table_name) );
        // $select->join(array( 'r'=>'radios'), 'ca.idr=r.idr', array('radio_name'), $select::JOIN_LEFT );

        if($category_name!==null)
            $select->where( new Predicate\Like('ca.category_name', '%'.$category_name.'%' ) );

        //LIKE DESDE LA TABLA
        if($like!==null):
            $select->where( new Predicate\Like('ca.category_name', '%'.$like.'%' ) );
        endif;

        // if($idr!==null)
        //     $select->where( array('ca.idr'=>$idr) );

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
        $exp_fecha=new Expression('date_format(category_date_create, "%d.%m.%Y")');
        $exp_hora=new Expression('time_format(category_date_create, "%H:%i")');

        $select->columns(array('*', 'category_date_create_fecha'=>$exp_fecha, 'category_date_create_hora'=>$exp_hora), '');
        $select->from(array( 'ca'=>$this->table_name) );

        $select->where(array('ca.idca'=>$idca) );
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
        $update->where(array('idca'=>$idca) );
        $statement = $this->sql->prepareStatementForSqlObject($update);
        $statement->execute();
    }

    public function delete($idca)
    {
        if($idca==null){
            return false;
        }

        $select = $this->sql->delete();
        $select->from($this->table_name);
        $select->where(array('idca'=>$idca) );

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
    }

    public function contCustom($category_slug=null, $category_name=null) 
    {
        // SI TODOS LOS PARAMETROS ESTAN EN NULL, RETORNAMOS 0
        if($category_slug===null and $category_name===null)
            return 0;

        $select = $this->sql->select();
        $select->columns(array( 'total'=> new Expression('count(*)' ) ), '');

        $select->from($this->table_name);
        
        if($category_slug!==null)
            $select->where( array('category_slug'=>$category_slug) );

        if($category_name!=null)
            $select->where(array('category_name'=>$category_name) );

        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        $result=$result->current();//
        return $result["total"];
    }
}

?>