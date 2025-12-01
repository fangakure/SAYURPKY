<?php
include "../config/db.php";

if(!isset($_GET['id'])){
    header("Location: edukasi.php");
    exit;
}

$id = intval($_GET['id']);
$conn->query("DELETE FROM tbl_edukasi WHERE id_edukasi=$id");

header("Location: edukasi.php");
exit;
?>
