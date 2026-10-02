<?php

$host = "127.0.0.1";
$usuario = "root";
$senha = "123456";
$banco = "industria_db";
$porta = 3306;

$conn = new mysqli($host, $usuario, $senha, $banco, $porta);

if ($conn->connect_error) {
    die("Falha na conexão com o banco de dados: " . $conn->connect_error);
}

$conn->set_charset("utf8");

?>