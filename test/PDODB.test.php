<?php
/**
 * 测试文件
 */
require_once '../module/PDODB.class.php';
$PDODB = new PDODB('127.0.0.1', '3306', 'wu', 'root', 'root');
$sql = "SELECT * FROM w_account WHERE acid=:acid";
var_dump($PDODB->query($sql, array('acid'=>1)));
$PDODB->setTableName('w_account');
var_dump($PDODB->find(array('acid'=>1)));
var_dump($PDODB->findAll());
var_dump($PDODB->findCount());
var_dump($PDODB->lastInsertId());