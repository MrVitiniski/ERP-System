<?php
echo "Caminho sugerido: " . $_SERVER['DOCUMENT_ROOT'] . "/sistema/api/conexao.php";
echo "<br>O arquivo existe? " . (file_exists($_SERVER['DOCUMENT_ROOT'] . "/sistema/api/conexao.php") ? "SIM" : "NÃO");
