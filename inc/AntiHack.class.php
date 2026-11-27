<?php

/**
* Класс фильтрации глобальных переменных
* Соавтор: Кредитор
* Icq: 441460
* Email: kpegumop@yandex.ru
* Сайт: http://gix.su - Безопасные сделки, модули и дизайны для ваших сайтов, seo скрипты, оптимизация, безопасность
* Любые скрипты на заказ
*/

class AntiHack {

     private $getarr = ''; // Ключ для распознавания массива
   
     private $input = array(); // Массив для фильтрации

     private $arraykey = 256; // максимальное количество символов в ключе массива

     private $arrayvalue = 100000; // максимальное количество символов в значении массива

     // Ключи для удаления из массива _SERVER
     private $arrayserverkey = 'COMSPEC;SYSTEMROOT;PATHEXT;UNIQUE_ID;PATH;GATEWAY_INTERFACE;SERVER_SIGNATURE;SERVER_ADMIN;PERL5LIB';


   function __construct()
   {
      $this->filter($this ->input, $this->getarr);
   }


   private function __cleaning($input, $keyss, $getarr)
   {
                 // PHP 8: urldecode()/mb_detect_encoding() throw TypeError for arrays (a[b][c]=1); PHP 5 only warned and dropped the value
                 if (is_array($input) || is_object($input)) return '';
                 if (!empty($input) && $keyss == 'value' && $getarr != 'post')$input = urldecode($input);
                 
/******                 $input = str_replace(array("\n", "\r", "\t"), null, trim($input));******/

                 // PHP 8.3 changed mb_detect_encoding(): text that is not valid UTF-8 (a cp1251 form, junk bytes) is reported as 'ASCII' there,
                 // PHP 5 - 8.1 said 'UTF-8' (or false). The 'ASCII' branch below converts from WINDOWS-1251: the result is made the same on every PHP 8.
                 $charset = mb_check_encoding($input, 'UTF-8') ? mb_detect_encoding($input) : 'ASCII';
                 
                 $input = (($charset != 'UTF-8')?iconv((($charset === 'ASCII')?'WINDOWS-1251':$charset), 'UTF-8', $input):$input);
                              
                 if (!empty($input) && $keyss === 'key')
                 {
                  $input = preg_replace('#[^a-z0-9-_]+#i', '', $input);
                  $input = (string)strip_tags($input);
                 
                 }
                 elseif (($getarr === 'get' || !empty($input)) && $keyss === 'value')
                 {

                 if ($getarr === 'get') 
                 {
                 $input = preg_replace('#[^(a-z0-9-_\(\)\&\=\?\;\:\.\/\]\[)|(\x7F-\xFF)|(\s)]+#is', '', $input );

                  $input = str_replace('../', '', $input);      
                 }
                 elseif ($getarr === 'post')
                 { 
                       $input = str_replace('javascript', 'jаvаsсriрt', $input);
                       $input = htmlentities($input, ENT_QUOTES, 'UTF-8');
                       $input = addslashes($input);
                       
                 }
                 elseif ($getarr === 'files')
                 {
                      if (!is_string($input)) 
                      {
                      $input = intval($input);
                      }
                      else
                      {
                      $input = preg_replace('#[^(a-zа-я0-9-_\(\)\&\=\?\;\:\.\/)|(\x7F-\xFF)|(\s)]+#is', '', $input );
                      }
                  
                 }
                 elseif ($getarr === 'cookie')
                 {
                 $input = preg_replace('#[^a-z0-9-_]+#i', '', $input);
                 }
                 elseif ($getarr === 'server')
                 {
                 $input = str_replace('\\', '/', $input);
                 $input = preg_replace('#[^a-z0-9-_\/\.\s\:\;\,\?\=\@]+#i', '', $input);
                 }
                 elseif ($getarr === 'request')
                 {
                      if (!is_string($input))
                      {
                      $input = intval($input);
                      }
                      else
                      {
                      $input = htmlentities($input, ENT_QUOTES, 'UTF-8');
                      }
                  
                 }
                 else
                 {
                 $input = (string)htmlentities( $input, ENT_QUOTES, 'UTF-8');
                 $input = strip_tags($input);
                 }
            }
            /*else
            {
            $input = null;
            }*/
       
       return ( $input );
       }



   private function __sorting($input, $getarr)
   {
      if (!is_array($input))return(array());
      
      $arr_delete = explode(';', $this->arrayserverkey);

      foreach($input as $key => $value)
     {
              
             if ($getarr === 'server') 
             {
             $key = strtoupper($key);
                  for ($i = 0; $i < count($arr_delete); $i++) 
                  {
                        if (strnatcasecmp($key, $arr_delete[$i]) === 0)$value = null;
                  }
            }

             if ((!$key || $key == '' || $value === null || isset($key[$this->arraykey])) || ($getarr === 'files' && (!isset($input[$key]['tmp_name']) || !is_string($input[$key]['tmp_name']) || !is_uploaded_file($input[$key]['tmp_name']))))continue;
              
             if (!is_array($value)) {
                   if (isset($value[$this->arrayvalue]))continue;
                    
                   $result[$this->__cleaning($key, 'key', $getarr)] = $this->__cleaning($value, 'value', $getarr);
             }
             else {
                   foreach($value as $item => $field) {
                          if ($field === '' || $field === null || isset($item[$this->arraykey]) || isset($field[$this->arrayvalue]))continue;
                          
                          $value[$this->__cleaning($item, 'key', $getarr)] = $this->__cleaning($field, 'value', $getarr);
                   }
                   $result[$this->__cleaning($key, 'key', $getarr)] = $value;
             }
       }
       return ((isset($result) ? $result : array()));
   }



   function filter($input, $getarr)
   {
      if (!is_array($input) || count($input) === 0) {
             return(array());
      }
             $getarr = strtolower($getarr);
             $getarr = str_replace(' ', '', trim($getarr ));
             return ($this->__sorting($input, $getarr));
   }



}//end AntiHack
?>