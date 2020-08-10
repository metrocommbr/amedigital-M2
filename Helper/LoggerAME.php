<?php

namespace AmeDigital\AME\Helper;

class LoggerAME
{
    private $_connection;

    public function __construct(\Magento\Framework\App\ResourceConnection $resource
    )
    {
        $this->_connection = $resource->getConnection();
    }
    public function log($message,$type="info",$url="",$input=""){
        $message = str_replace("'","",$message);
        $input = str_replace("'","",$input);
        $sql = "INSERT INTO ame_log (type,url,message,input,created_at) VALUES ".
            "('".$type."','".$url."','".$message."','".$input."',NOW())";
        $this->_connection->query($sql);
    }
}
