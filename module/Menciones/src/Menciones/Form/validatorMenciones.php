<?php
namespace Menciones\Form;
 
//Incluimos todo lo necesario
// use Zend\Form\Form;
// use Zend\InputFilter\InputFilter;
// use Zend\Validator;
// use Zend\I18n\Validator as I18nValidator;
 
class validatorMenciones{
   
    public function __construct(){
    }

    //FUNCION QUE VALIDA LOS PARAMETROS INGRESADOS
    public function validate($datos, $action='login'){

        $arr_return  = [];
        $anio_actual = date('Y');

    
        switch ($action) {
            case 'pauta_ingresar':
                //**************************************
                // VALIDAMOS PAUTAS INGRESAR ACTION
                //**************************************

                if( !isset($datos["title_pauta"]) or empty($datos["title_pauta"]) or strlen($datos["title_pauta"])>250 ){
                    return "Debe ingresar la pauta (Entre 1-250 caracteres).";
                }

                if( !isset($datos["dia_pauta"]) or empty($datos["dia_pauta"]) or strlen($datos["dia_pauta"])!=10 ){
                    return "Debe seleccionar el día de la pauta.";
                }

                if( !isset($datos["hora_pauta"]) or empty($datos["hora_pauta"]) or strlen($datos["hora_pauta"])!=5 ){
                    return "Debe seleccionar la hora de la pauta.";
                }

            break;

            case 'pauta_editar':

                //**************************************
                // VALIDAMOS PAUTAS EDITAR ACTION
                //**************************************

                if( !isset($datos["pauta"]) or !is_numeric($datos["pauta"]) or abs($datos["pauta"])<=0 or strlen($datos["pauta"])>11 ){
                    return "Pauta no encontrada.";
                }

                if( !isset($datos["title_pauta"]) or empty($datos["title_pauta"]) or strlen($datos["title_pauta"])>250 ){
                    return "Debe ingresar la pauta (Entre 1-250 caracteres).";
                }

                if( !isset($datos["dia_pauta"]) or empty($datos["dia_pauta"]) or strlen($datos["dia_pauta"])!=10 ){
                    return "Debe seleccionar el día de la pauta.";
                }

                if( !isset($datos["hora_pauta"]) or empty($datos["hora_pauta"]) or strlen($datos["hora_pauta"])!=5 ){
                    return "Debe seleccionar la hora de la pauta.";
                }
                
            break;

        }
      
              
        return true;
    }
}