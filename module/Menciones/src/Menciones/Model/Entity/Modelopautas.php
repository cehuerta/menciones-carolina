<?php
namespace Menciones\Model\Entity;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Predicate;

class Modelopautas{

    public $dbAdapter;
    public $sql;
    public $tabla = 'pautas';

    public function __construct($adapter)
    {
        $this->dbAdapter=$adapter;
        $this->sql = new Sql($this->dbAdapter);
    }

    public function add($datos) 
    {
        $insert = $this->sql->insert();
        $insert->into($this->tabla);
        $insert->values($datos);
        $statement = $this->sql->prepareStatementForSqlObject($insert);
        $statement->execute();

        return $this->dbAdapter->getDriver()->getLastGeneratedValue();
    }

    public function all( $query=array() ) 
    {
        $select         = $this->sql->select();
        $exp_fecha      = new Expression('date_format(pa.fecha_pauta, "%d.%m.%Y")');
        $exp_hora       = new Expression('time_format(pa.fecha_pauta, "%H:%i")');
        $exp_dia_pauta  = new Expression('date_format(pa.dia_pauta, "%d.%m.%Y")');
        $exp_hora_pauta = new Expression('time_format(pa.hora_pauta, "%H:%i")');

        $select->columns(array('*', 'fecha_pauta_fecha'=>$exp_fecha, 'fecha_pauta_hora'=>$exp_hora, 'dia_pauta_f'=>$exp_dia_pauta, 'hora_pauta_f'=>$exp_hora_pauta ), 'pa');
        $select->from(array( 'pa'=>$this->tabla) );
        $select->join(array( 'r'=>'radios'), 'pa.idr=r.idr', array('radio_name'), $select::JOIN_LEFT );

        //UTILIZADO PARA CALCULAR EL TOTAL DE RESULTADOS SEGUN LOS FILTROS
        $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS') );

        if(isset($query['search']) && $query['search']!==null)
            $select->where('( (pa.title_pauta LIKE "%'.$query['search'].'%") OR (pa.dia_pauta LIKE "%'.$query['search'].'%") )');


        if(isset($query['idr']) && $query['idr']!==null)
            $select->where(array('pa.idr'=>$query['idr']) );
        
        if(isset($query['pauta_leida']) && $query['pauta_leida']!==null)
            $select->where(array('pa.pauta_leida'=>$query['pauta_leida']));

        if(isset($query['not_idpauta']) && $query['not_idpauta']!==null)
            $select->where( new Expression('pa.idpauta NOT IN (?)', $query['not_idpauta'] ) );//SOLO 1 VALOR

        if(isset($query['dia_pauta']) && $query['dia_pauta']!==null)
            $select->where(array('pa.dia_pauta'=>(string)$query['dia_pauta']));

        if(isset($query['hora_pauta']) && $query['hora_pauta']!==null)
            $select->where(array('pa.hora_pauta'=>$query['hora_pauta']));

        if(isset($query['notification_hora']) && $query['notification_hora']!==null)
            $select->where('pa.hora_pauta<="'.$query['notification_hora'].'"');

        if(isset($query['limit']) && $query['limit']!==null)
            $select->limit( (int)$query['limit'] );

        if(isset($query['start']) && $query['start']!==null)
            $select->offset( (int)$query['start'] );

        if(isset($query['order']) && $query['order']!==null)
            $select->order($query['order']);
        else
            $select->order('pa.title_pauta ASC');
        
        $statement = $this->sql->prepareStatementForSqlObject($select);
        // echo "<pre>";
        // print_r($statement);
        // echo "</pre>";
        $result    = $statement->execute();
        
        $resultSet = new ResultSet;
        $result    = $resultSet->initialize($result);
        return $result->toArray();
    }


    public function found() 
    {
        //UTILIZADO PARA OBTENER EL TOTAL DE RESULTADOS SEGUN LA OBTENCION DE RESULTADOS DEL METODO "ALL"
        $select = $this->sql->select();
        $select->columns(array( new Expression('FOUND_ROWS() as total') ), '');
        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();
        $result    = $result->current();
        return $result["total"];
    }

    
    public function get( $query=array() ) 
    {
        if( !is_array($query)|| count($query)<=0 )
            return null;

        $select         = $this->sql->select();
        $exp_fecha      = new Expression('date_format(pa.fecha_pauta, "%d.%m.%Y")');
        $exp_hora       = new Expression('time_format(pa.fecha_pauta, "%H:%i")');
        $exp_dia_pauta  = new Expression('date_format(pa.dia_pauta, "%d.%m.%Y")');
        $exp_hora_pauta = new Expression('time_format(pa.hora_pauta, "%H:%i")');

        $select->columns(array('*', 'fecha_pauta_fecha'=>$exp_fecha, 'fecha_pauta_hora'=>$exp_hora, 'dia_pauta_f'=>$exp_dia_pauta, 'hora_pauta_f'=>$exp_hora_pauta), 'pa');
        $select->from(array( 'pa'=>$this->tabla) );
        $select->join(array( 'r'=>'radios'), 'pa.idr=r.idr', array('radio_name'), $select::JOIN_LEFT );

        if(isset($query['idpauta']) && $query['idpauta']!==null)
            $select->where(array('pa.idpauta'=>$query['idpauta']) );

        if(isset($query['idr']) && $query['idr']!==null)
            $select->where(array('pa.idr'=>$query['idr']) );

        if(isset($query['dia_pauta']) && $query['dia_pauta']!==null)
            $select->where(array('pa.dia_pauta'=>$query['dia_pauta']) );

        if(isset($query['hora_pauta']) && $query['hora_pauta']!==null)
            $select->where(array('pa.hora_pauta'=>$query['hora_pauta']) );

        if(isset($query['pauta_leida']) && $query['pauta_leida']!==null)
            $select->where(array('pa.pauta_leida'=>$query['pauta_leida']) );

        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();
        return $result->current();
    }


    public function update($data, $idpauta) 
    {
        if($idpauta===null)
            return null;

        $update = $this->sql->update();
        $update->table($this->tabla);
        $update->set($data);
        $update->where( array('idpauta'=>$idpauta) );
        $statement = $this->sql->prepareStatementForSqlObject($update);
        $statement->execute();
    }


    public function count($query=array()) 
    {
        $select = $this->sql->select();
        $select->columns(array(new Expression('count(*) as total') ), '');
        $select->from($this->tabla);

        if(isset($query['idr']) && $query['idr']!==null)
            $select->where(array('idr'=>$query['idr']));

        if(isset($query['dia_pauta']) && $query['dia_pauta']!==null)
            $select->where(array('dia_pauta'=>$query['dia_pauta']));

        if(isset($query['hora_pauta']) && $query['hora_pauta']!==null)
            $select->where(array('hora_pauta'=>$query['hora_pauta']));

        if(isset($query['pauta_leida']) && $query['pauta_leida']!==null)
            $select->where(array('pauta_leida'=>$query['pauta_leida']));

        $select->limit(1);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();
        $result    = $result->current();
        return $result["total"];
    }


    public function delete($query=array() ) 
    {
        if( !is_array($query) || count($query)<=0 )
            return null;

        $select = $this->sql->delete();
        $select->from($this->tabla);

        if(isset($query['idpauta']) )
            $select->where(array('idpauta'=>$query['idpauta']) );

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();
    }
}


?>