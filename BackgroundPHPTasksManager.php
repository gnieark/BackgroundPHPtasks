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

    /*
    * Add a task that, if not running will be executed only when no others tasks are terminated
    * If $startnow is true, it wil be launched now
    */
    public function add_task_on_queue (BackgroundPHPTask $backgroundPHPTask, bool $startnow = false)
    {
        if(empty($backgroundPHPTask->get_pifFile() ))
        {
            $backgroundPHPTask-> set_pidFile ( $this->get_pid_file_path() );
        }



        if($startnow && !($backgroundPHPTask->get_status() == "running") )
        {
            $backgroundPHPTask->exec();
        }
        $this->tasks[] = $backgroundPHPTask;
        $this->check_queue();
        return $this;

    }

    public function check_queue()
    {
        $lastStatus = "terminated";
        foreach($this->tasks as $task)
        {
            if( $task->get_status() == "running" )
            {
                $this->save();
                return $this;
            }elseif($task->get_status() == "pending"){
                $task->exec();
                $this->save();
                return $this;
            }
        }
        $this->save();
        return $this;
    } 

    /*
    * @input $delay : seconds
    *   
    */
    public function schedule_check_queue($delay)
    {

    }

    public function purge_terminated_tasks()
    {
        for ($i = 0; $i < count ($this->tasks); $i++)
        {
            if( $this->tasks[$i]->get_status() == "terminated" )
            {
                unset($this->tasks[$i]);
            }
        }
        $this->clean_pid_file(true);
        $this->save();
        return $this;
    }
    
    /*
    * Remove non existing PID on pid file
    */
    private function clean_pid_file($killUnknowedProcess = false)
    {
        $existingPids = array();
        foreach( $this->tasks as $task)
        {
            if(!empty($task->get_pid()))
            {
                $existingPids[] = $task->get_pid();
            }
        }

        if ($killUnknowedProcess)
        {
            $pidFileResource = @fopen($this->get_pid_file_path(), "r");
            if($pidFileResource)
            {
                while (($pidLine = fgets($pidFileResource, 4096)) !== false) {
                    if ((!in_array($buffer, $existingPids)) && ($this->isProcessRunning($buffer)))
                    {
                        posix_kill( $buffer, SIGTERM );
                    }
                }
            }
            fclose($pidFileResource);
        }


        $pidFileResource = fopen($this->get_pid_file_path());
        if (flock($pidFileResource, LOCK_EX)) { // lock
            ftruncate($pidFileResource, 0);

            foreach($existingPids as $pid)  {  
                fwrite($pidFileResource, $pid . "\n");
            }

            fflush($pidFileResource);     
            flock($pidFileResource, LOCK_UN);   
        } else {
            throw new LockException('Could not lock the pidfile');
        }
        fclose( $this->get_pid_file_path() );
    }

    private function isProcessRunning($pid)
    {
        // Warning: this will only work on Unix
        return ($pid !== '') && file_exists("/proc/$pid");
    }




}