<?php
namespace Application\Model\Entity;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Resultset\Resultset;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Predicate\Expression;

class Modeloradios{

    public $dbAdapter;
    public $sql;
    public $table_name = 'radios';

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

    public function all($limit=null, $start=null, $order=null, $search=null) 
    {
        $select = $this->sql->select();
        $select->columns(array('*'), '');
        $select->from(array( 'r'=>$this->table_name) );


        if($search!==null)
            $select->where(' (r.radio_name LIKE "%'.$search.'%") ');

        if($order!==null)
            $select->order($order);

        if($limit!==null)
            $select->limit($limit);
        if($start!==null)
            $select->offset($start);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();

        $resultSet = new Resultset;
        $result=$resultSet->initialize($result);
        return $result->toArray();
    }

    public function get($idr) 
    {
        $select = $this->sql->select();
        $select->columns(array('*'), '');
        $select->from(array( 'r'=>$this->table_name) );


        $select->where('r.idr="'.$idr.'"');
        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        return $result->current();
    }
    

    public function update($datos, $idr)
    {
        $update = $this->sql->update();
        $update->table($this->table_name);
        $update->set($datos);
        $update->where('idr="'.$idr.'"');
        $statement = $this->sql->prepareStatementForSqlObject($update);
        $statement->execute();
    }


    public function delete($idr)
    {
        if($idr==null){
            return null;
        }

        $select = $this->sql->delete();
        $select->from($this->table_name);
        $select->where('idr='.$idr);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
    }


    public function contCustom($radio_slug = null )
    {
        $select = $this->sql->select();
        $select->columns(array( 'total'=>new Expression('count(*)') ), '');

        $select->from($this->table_name);

        if( $radio_slug!==null )
            $select->where( array('radio_slug'=>$radio_slug) );

        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        $result=$result->current();//
        return $result["total"];
    }


}


?>