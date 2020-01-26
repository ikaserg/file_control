<?php

//----------------------------------------------------
//  Статус файла
//      0 - не обрабатывался
//      1 - файл соответсвует резервному
//      2 - файл не совпадает с резервом
//      3 - файла нет в резерве, но есть на диске
//      4 - файла нет на диске, но есть в резерве
//----------------------------------------------------
class CheckedFile{
    public  $rel_path;
    public  $md5;
    public  $zip_content;
    public  $status;
    function __construct($path){
        $this->rel_path = $path;
        $this->status = 0;        
    }
}

class DeletedFile{
    public  $rel_path;
    public  $status;
    public  $msg;
    function __construct($rel_path, $status, $msg){
        $this->rel_path = $rel_path;
        $this->status = $status;
        $this->msg = $msg;
    }
}

function zip($path, $del){
    $zip = new ZipArchive();
    if ($zip->open($path.".zip", ZipArchive::CREATE)!==TRUE) {
        echo("<div class='error zip'>cannot open <$path.zip> </div>");
    }
    $zip->addFile($path, basename($path));
    if ($del == 1)
        unlink($path);
    return $path.".zip";    
}

function unzip($data){
    // magic
    $head = unpack("Vsig/vver/vflag/vmeth/vmodt/vmodd/Vcrc/Vcsize/Vsize/vnamelen/vexlen", substr($data,0,30));
    $filename = substr($data,30,$head['namelen']);
    $raw = gzinflate(substr($data,30+$head['namelen']+$head['exlen'],$head['csize']));    
    return $raw;
}

function ins_one_metr($t, $path, $level, $pt){        
    if ($pt == 1)
        $project_id = $t->project_id;
    else
        $project_id = $t->onemetr_id;    
    $ext = getExtension($path);
    if (($ext != "jpg") && ($ext != "png")){
        $md5 = md5_file($path);
        $sql = "insert into tbl_file_controls(project_id, rel_path, md5, content)
                    values ($project_id, '$path', '$md5',
                    '".base64_encode(gzcompress(file_get_contents($path)))."');";
        #unlink($zip_name);
        mysqli_query($t->conn, $sql);
        if (!mysqli_commit($t->conn))
            echo "<div>Error: " . mysqli_error($t->conn)."</div>";
        echo "<div class='file_item' >".$path."    ".$md5."</div>";
    }    
}

function check_file($t, $path, $level){
    // Не обрабатываем картинки
    $res = new CheckedFile($path);
    $ext = getExtension($path);
    if (($ext != "jpg") && ($ext != "png")){
        $res->md5 = md5_file($path);
        $res->zip_content = base64_encode(gzcompress(file_get_contents($path)));
        $sql = "select rel_path, md5, content
                  from tbl_file_controls
                 where project_id = $t->onemetr_id
                   and rel_path = '$path'";
        #echo("<div>$path</div>");
        $result = $t->conn->query($sql);
        if ($result->num_rows == 0){
            $res->status = 3;
            $t->file_num = $t->file_num + 1;
        }
        else{
            $row = $result->fetch_assoc();
            if($res->md5 != $row["md5"])
                $res->status = 2;    
            else
                $res->status = 1;    
        }
    }    
    if (($res->status != 0) && ($res->status != 1))
        array_push($t->file_list, $res);
    return $res;
}

class FileController{
    public $project_id;
    public $conn;
    public $onemetr_id = 2;
    public $del_inject = 0;
    public $file_num = 0;
    public $break = 0;
    public $file_list;
    
    function __construct($p_conn) {
        $this->conn = $p_conn;
        $this->init_project_id();
        $this->file_list = array();
    }    

    function log_replace_file($path, $data ="", $md5 = ""){
        if ($data == "")
            $data = file_get_contents($path);
        if ($md5 == "")
            $md5 = md5_file($path);
        $sql = "insert into tbl_deleted_file(project_id, act_type_id, rel_path, md5, content)
                    values($this->project_id, 2, '$path', '$md5', '".base64_encode(gzcompress($data))."');";
        #echo "<div>$sql</div>";
        if(!mysqli_query($this->conn, $sql))
            echo "<div>Error: " . mysqli_error($this->conn)."</div>";
        if (!mysqli_commit($this->conn))
            echo "<div>Error: " . mysqli_error($this->conn)."</div>";
                                
    }

    function init_project_id(){
        $sql = "select p.project_id
                  from tbl_projects p 
                 where p.document_root = '".$_SERVER['DOCUMENT_ROOT']."'";
        $result = $this->conn->query($sql);
        if ($result->num_rows != 0) {
            $row = $result->fetch_assoc();
            $this->project_id = $row["project_id"];
        }
        else
            $this->project_id = -1;
    }

    function init_one_metr($path){
        $del_sql = "delete from tbl_file_controls where project_id = $p;";
        if (!mysqli_query($this->conn, $del_sql))
            echo ("<div>Error clear files: " . mysqli_error($this->conn)."</div>");
        $this->traversalFileTree($path, function($this, $path, $level) {ins_one_metr($this, $path, $level, 0);});
        #;
    }

    function init_project($path){
        $del_sql = "delete from tbl_file_controls where project_id = $p;";
        if (!mysqli_query($this->conn, $del_sql))
            echo ("<div>Error clear files: " . mysqli_error($this->conn)."</div>");
        $this->traversalFileTree($path, function($this, $path, $level) {ins_one_metr($this, $path, $level, 1);});
    }

    function control_files($path){
        $file_num = 0;
        $break = 0;
        $this->file_list = array();
        $this->traversalFileTree($path, function($this, $path, $level) {check_file($this, $path, $level);});
        return $this->file_list;
    }

    function delete_files($filelist){
        $delete_result = array();
        foreach($filelist as $f){
            #echo ($_SERVER['DOCUMENT_ROOT']."/".$f['rel_path']);
            try{
                unlink($_SERVER['DOCUMENT_ROOT']."/".$f['rel_path']);
                array_push($delete_result, new DeletedFile($_SERVER['DOCUMENT_ROOT']."/".$f['rel_path'], 0, ''));
            }    
            catch (Exception $e){
                array_push($delete_result, new DeletedFile($_SERVER['DOCUMENT_ROOT']."/".$f['rel_path'], 
                                           1,
                                           $e->getMessage()));
            }
        }
        return $delete_result;
    }

    // Рекурсивный Обход дерева файлов
    function traversalFileTree($path, $hook, $level = 0) {    
        #echo "<div class='file_item'>$path</div>";
        if ($this->break == 0){
            $handle = opendir($path);
            while($file = readdir($handle)) { 
                if ($this->break != 0)
                    break;
                if (is_file ($path."/".$file)){
                    $hook($this, $path."/".$file, $level);
                }       
                if (is_dir ($path."/".$file) && ($file != ".") && ($file != "..")){
                    if ($path != "./tmp"){
                        $this->traversalFileTree($path."/".$file, $hook, $level + 1);
                    }    
                }    
            }
            closedir($handle);
        }    
    }

}

function getExtension($filename) {
    return substr($filename, strrpos($filename, ".") + 1);    
  }

function extra_files($conn, $porject_id, $omemetr_id){
    $sql = "select 
              from tbl_file_controls p
              left join tbl_file_controls m on p.rel_path = m.rel_path 
                                           and m.project_id = $onemetr_id 
             where p.project_id = $project_id 
               and m.rel_path is null ";

}

function get_project_id($conn){
    $sql = "select p.project_id
              from tbl_projects p 
             where p.document_root = '".$_SERVER['DOCUMENT_ROOT']."'";
    $result = $conn->query($sql);
    if ($result->num_rows != 0) {
        $row = $result->fetch_assoc();
        return $row["project_id"];
    }
    else
        return -1;
}

$servername = "localhost";
$username = "file_control";
$password = "xzqL|UFLv1a?GZk";
$dbname = "ikaserg_fc";

$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$fc = new FileController($conn);
if ($_GET['action'] == 'init_one_metr')
    $fc->init_one_metr(".");

if ($_GET['action'] == 'init_qwerty')
    $fc->init_project(".");

if ($_GET['action'] == 'diff'){
    header('Content-Type: application/json');
    echo (json_encode($fc->control_files(".")));
}    

if ($_GET['action'] == 'delete_injected'){
    header('Content-Type: application/json');
    echo(json_encode($fc->delete_files(json_decode(file_get_contents('php://input'), true)['delete'])));
}

mysqli_close($conn);
?>