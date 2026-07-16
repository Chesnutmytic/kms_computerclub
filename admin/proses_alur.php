<?php
session_start(); require_once '../config/koneksi.php';
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) { header('Location: ../login.html'); exit; }
function alurBack(string $type, string $msg): void { $_SESSION['flash']=['type'=>$type,'msg'=>$msg]; header('Location: kelola_alur.html'); exit; }
$action=$_GET['action']??''; $id=(int)($_POST['id_alur']??$_GET['id']??0);
try {
 if ($action==='create' && $_SERVER['REQUEST_METHOD']==='POST') { $status=$_POST['status']??'Draft'; if(!in_array($status,['Draft','Published'],true)) throw new RuntimeException('Status tidak valid.'); $conn->prepare('INSERT INTO ALUR_PEMBELAJARAN (id_user,nama_alur,tingkat_level,status,tgl_dibuat) VALUES (:user,:nama,:level,:status,CURDATE())')->execute([':user'=>$_SESSION['id_user'],':nama'=>trim($_POST['nama_alur']??''),':level'=>trim($_POST['tingkat_level']??''),':status'=>$status]); alurBack('success','Alur berhasil dibuat.'); }
 if ($action==='edit' && $_SERVER['REQUEST_METHOD']==='POST' && $id>0) { $status=$_POST['status']??'Draft'; if(!in_array($status,['Draft','Published'],true)) throw new RuntimeException('Status tidak valid.'); $conn->prepare('UPDATE ALUR_PEMBELAJARAN SET nama_alur=:nama, tingkat_level=:level, status=:status WHERE id_alur=:id')->execute([':nama'=>trim($_POST['nama_alur']??''),':level'=>trim($_POST['tingkat_level']??''),':status'=>$status,':id'=>$id]); alurBack('success','Alur berhasil diperbarui.'); }
 if ($action==='delete' && $id>0) { $conn->prepare('DELETE FROM ALUR_PEMBELAJARAN WHERE id_alur=:id')->execute([':id'=>$id]); alurBack('success','Alur berhasil dihapus.'); }
 if ($action==='add_detail' && $_SERVER['REQUEST_METHOD']==='POST' && $id>0) { $arsip=(int)($_POST['id_arsip']??0); if($arsip<=0) throw new RuntimeException('Materi belum dipilih.'); $conn->prepare('INSERT INTO DETAIL_ALUR (id_alur,id_arsip) SELECT :alur,:arsip WHERE NOT EXISTS (SELECT 1 FROM DETAIL_ALUR WHERE id_alur=:alur2 AND id_arsip=:arsip2)')->execute([':alur'=>$id,':arsip'=>$arsip,':alur2'=>$id,':arsip2'=>$arsip]); alurBack('success','Materi ditambahkan ke alur.'); }
 if ($action==='remove_detail') { $detail=(int)($_GET['id_detail']??0); if($detail<=0) throw new RuntimeException('Detail alur tidak valid.'); $conn->prepare('DELETE FROM DETAIL_ALUR WHERE id_detail=:id')->execute([':id'=>$detail]); alurBack('success','Materi dihapus dari alur.'); }
 throw new RuntimeException('Aksi alur tidak dikenali.');
} catch (Throwable $e) { alurBack('danger',$e->getMessage()); }
