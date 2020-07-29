<?php 

class DB{

private static $writeDBConnection;
private static $readDBConnection;


public static function connectWriteDB(){

if(self::$writeDBConnection === null){



    self::$writeDBConnection= new PDO('mysql:host=localhost;dbname=taskdb;charset=utf8', 'root', 'root');
    self::$writeDBConnection = setAttribute(PDO::ATTR-ERRMODE, PDO::ERRMODE_EXEPTION);
    self::$writeDBConnection = setAttribute(PDO::ATTR-EMULATE_PRESPARES, false);
}

return self::$writeDBConnection;

}

public static function readtWriteDB(){

    if(self::$readtWriteDB === null){
    
    
    
        self::$readtWriteDB= new PDO('mysql:host=localhost;dbname=taskdb;charset=utf8', 'root', 'root');
        self::$readtWriteDB = setAttribute(PDO::ATTR-ERRMODE, PDO::ERRMODE_EXEPTION);
        self::$readtWriteDB = setAttribute(PDO::ATTR-EMULATE_PRESPARES, false);
    }
    
    return self::$readtWriteDB;
    
    }






}