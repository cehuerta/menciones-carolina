<?php
namespace Application\Model\Entity;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Predicate\Expression;

use Zend\Validator;
use Zend\Filter;
use Zend\I18n\Validator as I18nValidator;
use Zend\Crypt\Password\Bcrypt;


class Modelofunctions{


    public $dbAdapter;
    public $sql;

    public function __construct($adapter)
    {
        $this->dbAdapter=$adapter;
        $this->sql = new Sql($this->dbAdapter);
    }

    public function uploadFile( $file=array(), $rutaRaiz=null, $name_image=null, $limit_size=null ) 
    {
        $arr_return=array();
        if( !isset($file["file"]) or empty($file["file"]["name"]) or empty($file["file"]["tmp_name"]) 
                or $rutaRaiz==null or $name_image==null )
        {
            $arr_return["status"]="error";
            $arr_return["msj"]="Se produjo un error antes de comenzar a subir el archivo.";
            return $arr_return;
        }


        //+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        //VALIDAMOS EL PESO DEL ARCHIVO
        if( $limit_size!=null and is_array($limit_size) 
                and isset($limit_size["min"]) and isset($limit_size["max"]) )
        {

            $validator = new Validator\File\Size(array(
                'min' => $limit_size["min"], 
                'max' => $limit_size["max"]
            ));
            if( !$validator->isValid( $file["file"]["tmp_name"] ) ){
                $json["status"]="error";
                $json["msj"]="Debe seleccionar un archivo que no sobrepase los ".$limit_size["max"]." de peso.";
                return $json;
            }
        }
        //+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++


        //UPLOAD DE IMAGEN ZF2
        $validator = new Validator\File\UploadFile();
        $filter = new Filter\File\RenameUpload(array(
                "target"=>$rutaRaiz.'/'.$name_image,
            )
        );
        if ( !$filter->filter($file["file"]) ) {
            $arr_return["status"]="error";
            $arr_return["msj"]="Se produjo un error mientras se subia el archivo.";
            return $arr_return;
        }
        //.-FIN UPLOAD DE IMAGEN ZF2

        $arr_return["status"]="ok";
        $arr_return["msj"]="";
        return $arr_return;
    }

    //FUNCION DEL RESIZE
    public function resizeImg($rutaImg, $rutaNewImg, $w, $h)
    {
        try {
            // ----------------------------------------------------------------
            require_once './vendor/libreriaphpthumb/PHPThumb/ThumbLib.inc.php';
            $src=$rutaImg;
            $saveTo=$rutaNewImg;
            $thumb = \PhpThumbFactory::create($src);
            $thumb->adaptiveResize($w, $h); 
            $thumb->save($saveTo);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    function resize_then_crop( $filein, $fileout, $imagethumbsize_w,$imagethumbsize_h,$red,$green,$blue)
    {
        $white=0;
        // Get new dimensions
        // list($width, $height) = getimagesize($filein);
        // $new_width = $width * $percent;
        // $new_height = $height * $percent;

           if (preg_match("/.jpg/i", "$filein")) { $format = 'image/jpeg'; }
           if (preg_match("/.gif/i", "$filein")) { $format = 'image/gif'; }
           if (preg_match("/.png/i", "$filein")) { $format = 'image/png'; }
           
           switch($format)
           {
                case 'image/jpeg': $image = imagecreatefromjpeg($filein); break;
                case 'image/gif':  $image = imagecreatefromgif($filein);  break;
                case 'image/png':  $image = imagecreatefrompng($filein);  break;
            }

        $width = $imagethumbsize_w ;
        $height = $imagethumbsize_h ;
        list($width_orig, $height_orig) = getimagesize($filein);

        if ($width_orig < $height_orig) {
          $height = ($imagethumbsize_w / $width_orig) * $height_orig;
        } else {
            $width = ($imagethumbsize_h / $height_orig) * $width_orig;
        }

        if ($width < $imagethumbsize_w)
        //if the width is smaller than supplied thumbnail size 
        {
        $width = $imagethumbsize_w;
        $height = ($imagethumbsize_w/ $width_orig) * $height_orig;;
        }

        if ($height < $imagethumbsize_h)
        //if the height is smaller than supplied thumbnail size 
        {
        $height = $imagethumbsize_h;
        $width = ($imagethumbsize_h / $height_orig) * $width_orig;
        }

        $thumb = imagecreatetruecolor($width , $height);  
        $bgcolor = imagecolorallocate($thumb, $red, $green, $blue);   
        ImageFilledRectangle($thumb, 0, 0, $width, $height, $bgcolor);
        imagealphablending($thumb, true);

        imagecopyresampled($thumb, $image, 0, 0, 0, 0,
        $width, $height, $width_orig, $height_orig);
        $thumb2 = imagecreatetruecolor($imagethumbsize_w , $imagethumbsize_h);
        // true color for best quality
        $bgcolor = imagecolorallocate($thumb2, $red, $green, $blue);   
        ImageFilledRectangle($thumb2, 0, 0,
        $imagethumbsize_w , $imagethumbsize_h , $white);
        imagealphablending($thumb2, true);

        $w1 =($width/2) - ($imagethumbsize_w/2);
        $h1 = ($height/2) - ($imagethumbsize_h/2);

        imagecopyresampled($thumb2, $thumb, 0,0, $w1, $h1,
        $imagethumbsize_w , $imagethumbsize_h ,$imagethumbsize_w, $imagethumbsize_h);


        if ($fileout !="")imagepng($thumb2, $fileout); 

    }

    public function createExtracto($description, $charlength=20){
        // $arr =  str_word_count( $text, 1 ,'-_(){})("“”!¡.,;@\nÁÉÍÓÚàáéèíìóòúùñÑçÜü¿?\'0...9');
        // $str = '';
        // $t_arr=count($arr);
        // $puntos_suspensivos='...';
        // if( $t_arr<$t_palabras ){
        //     $t_palabras=$t_arr;
        //     $puntos_suspensivos='';
        // }

        // for ($i=0; $i < $t_palabras ; $i++) {

        //     if($i == 0){
        //         $str .= $arr[$i];
        //     }
        //     else{
        //         $str .= ' '.$arr[$i];
        //     }
        // }

        // return $str.$puntos_suspensivos;

        $charlength++;

        if ( mb_strlen( $description ) > $charlength ) {
            $subex = mb_substr( $description, 0, $charlength - 5 );
            $exwords = explode( ' ', $subex );
            $excut = - ( mb_strlen( $exwords[ count( $exwords ) - 1 ] ) );
            if ( $excut < 0 ) {
                return mb_substr( $subex, 0, $excut );
            } else {
                return $subex;
            }
            return '[...]';
        } else {
            return $description;
        }
    }

    //------------------------------------
    //elimina todos los archivos y directorios de una carpeta, de forma recursiva
    public function eliminarDirectorio($carpeta){ 

        foreach(glob($carpeta . "/*") as $archivos_carpeta)
        {
         
            if (is_dir($archivos_carpeta))
            {
                $this->eliminarDirectorio($archivos_carpeta);
            }
            else
            {
                unlink($archivos_carpeta);
            }
        }
         
        rmdir($carpeta);
        return true;
    }

    public function sanear_string($string)
    {
        $string = trim($string);

        $string = str_replace(
            array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
            array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
            $string
        );

        $string = str_replace(
            array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
            array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
            $string
        );

        $string = str_replace(
            array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
            array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
            $string
        );

        $string = str_replace(
            array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
            array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
            $string
        );

        $string = str_replace(
            array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
            array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
            $string
        );

        $string = str_replace(
            array('ñ', 'Ñ', 'ç', 'Ç'),
            array('n', 'N', 'c', 'C',),
            $string
        );

        $string = str_replace(" ","-",
            $string
        );

        //Esta parte se encarga de eliminar cualquier caracter extraño
        $string = str_replace(
            array("\\", "¨", "º", "~", "_",
                 "#", "@", "|", "!", "\"",
                 "·", "$", "%", "&", "/",
                 "(", ")", "?", "'", "¡",
                 "¿", "[", "^", "`", "]",
                 "+", "}", "{", "¨", "´",
                 ">", "< ", ";", ",", ":",
                 ".","¬", "M²"),
            '-',
            $string
        );

        return $string;
    }


    public function createKey(){
        $seed = str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'); // and any other characters
        shuffle($seed); // probably optional since array_is randomized; this may be redundant
        $rand = '';
        foreach (array_rand($seed, 5) as $k) 
            $rand .= $seed[$k];
     
        return $rand;
    }


    public function diasName($tmp_dias){
        $dias=explode(',', $tmp_dias);
        $diasName='';
        $arr_diasName=array();
        foreach ($dias as $value) {
            switch ($value) {
                case '1':
                    $arr_diasName[]= "Lunes";
                    break;
                case '2':
                    $arr_diasName[]= "Martes";
                    break;
                case '3':
                    $arr_diasName[]= "Miercoles";
                    break;
                case '4':
                    $arr_diasName[]= "Jueves";
                    break;
                case '5':
                    $arr_diasName[]= "Viernes";
                    break;
                case '6':
                    $arr_diasName[]= "Sabados";
                    break;
                case '7':
                    $arr_diasName[]= "Domingos";
                    break;
                default:
                    break;
            }
        }
        $diasName=implode(', ', $arr_diasName);
        return $diasName;
    }
    
}


?>