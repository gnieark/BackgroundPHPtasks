<?php

class BackgroundTasksManager
{

    private $base_path; //where pid file, logs and output are stored
    private $tasks = array();
    private function get_pid_file_path()
    {
        return $this->base_path . "/TaskManager.pid";
    }
    private function get_backup_file()
    {
        return $this->base_path . "/TaskManager.serialized";
    }
    public function __construct($base_path)
    {
        $this->base_path = $base_path;
        $this->load();
    }

    public function load(){
        if(file_exists($this->get_backup_file())){
            $arr = unserialize (file_get_contents( $this->get_backup_file() ));
            $this->base_path = $arr['base_path'];
            $this->tasks = $arr['tasks'];
        }
    }

    public function save(){
        $arr = array(
            "base_path" => $this->base_path,
            "tasks"     => $this->tasks
        );
        file_put_contents($this->get_backup_file() , serialize($arr));
    }



}