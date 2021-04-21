<?php
namespace Application\Model\Entity;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Predicate;

class Modeloiconos{

    public $dbAdapter;
    public $sql;

    public function __construct($adapter)
    {
        $this->dbAdapter=$adapter;
        $this->sql = new Sql($this->dbAdapter);
    }


    public function add($datos) 
    {
        $insert = $this->sql->insert();
        $insert->into('iconos');
        $insert->values($datos);
        $statement = $this->sql->prepareStatementForSqlObject($insert);
        $statement->execute();

        return $this->dbAdapter->getDriver()->getLastGeneratedValue();
    }


    public function all($icon_original_name=null, $icon_name=null, $icon_status=null, $start=0, $limit=20) 
    {
        $select = $this->sql->select();
        $select->columns(array('*'), '');
        $select->from(array( 'i'=>'iconos') );

        if($icon_original_name!==null)
            $select->where( new Predicate\Like('i.icon_original_name', '%'.$icon_original_name.'%' ) );
        if($icon_name!==null)
            $select->where( new Predicate\Like('i.icon_name', '%'.$icon_name.'%' ) );
        if($icon_status!==null)
            $select->where( array('i.icon_status'=>$icon_status) );

        $select->offset($start);
        $select->limit($limit);
 
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

    public function get($idico) 
    {
        $select = $this->sql->select();
        $exp_fecha=new Expression('date_format(icon_date_create, "%d.%m.%Y")');
        $exp_hora=new Expression('time_format(icon_date_create, "%H:%i")');

        $select->columns(array('*', 'icon_date_create_fecha'=>$exp_fecha, 'icon_date_create_hora'=>$exp_hora), '');
        $select->from(array( 'i'=>'iconos') );


        $select->where('i.idico="'.$idico.'"');
        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        return $result->current();
    }
    



    public function update($datos, $idico)
    {
        $update = $this->sql->update();
        $update->table('iconos');
        $update->set($datos);
        $update->where( array('idico'=>$idico) );
        $statement = $this->sql->prepareStatementForSqlObject($update);
        $statement->execute();
    }



    public function delete($idico=null)
    {
        if($idico===null){
            return null;
        }

        $select = $this->sql->delete();
        $select->from('iconos');
        if($idico!==null)
            $select->where( array('idico'=>$idico) );

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
    }


    public function contCustom($icon_status=null, $icon_name=null) 
    {
        // SI TODOS LOS PARAMETROS ESTAN EN NULL, RETORNAMOS 0
        if($icon_status===null and $icon_name===null)
            return 0;

        $select = $this->sql->select();
        $select->columns(array( 'total'=> new Expression('count(*)' ) ), '');

        $select->from('iconos');
        if($icon_status!==null)
            $select->where( array('icon_status'=>$icon_status) );
        
        if($icon_name!==null)
            $select->where(array('icon_name'=>$icon_name) );
        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result=$statement->execute();
        $result=$result->current();//
        return $result["total"];
    }

}


?>