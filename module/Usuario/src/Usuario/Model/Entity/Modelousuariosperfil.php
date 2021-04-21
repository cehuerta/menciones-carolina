<?php
namespace Usuario\Model\Entity;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Resultset\Resultset;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Predicate\Expression;

class Modelousuariosperfil{


    public $dbAdapter;

    public function __construct($adapter)
    {
              $this->dbAdapter=$adapter;
    }


    public function all() 
    {
        $query="SELECT * FROM usuarios_profiles ";
        $result=$this->dbAdapter->query($query, Adapter::QUERY_MODE_EXECUTE);
        return $result->toArray();
    }

    public function get($idprof) 
    {
        $query="SELECT * FROM usuarios_profiles WHERE idprof='".$idprof."' ";
        $result=$this->dbAdapter->query($query, Adapter::QUERY_MODE_EXECUTE);
        return $result->current();
    }

}


?>