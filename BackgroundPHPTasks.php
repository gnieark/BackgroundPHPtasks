<?php
class BackgroundPHPTask
{
  

    private $pidFile = "";
    private $pid;
    private $outputFile = "/dev/null";
    private $phpScript;
    private $identifier =""; //not the PID, juste another identifier if needed
    private $args = array();

    private $status = "pending"; //should be pending/running/terminated


    public function get_status()
    {
        if( ( $this->status == "running" ) && (!$this->is_running()) )
        {
            $this->status = "terminated";
        }
        return $this->status;
    }

    public function get_pid()
    {
        return $this->pid;
    }

    public function get_pifFile()
    {
        return $this->pidFile;
    }

    private function get_pid_from_pidfile()
    {
        
        $data = file($this->pidFile);
        return intval( $data[count($data) -1] );
    }

    public function set_phpScript(string $phpScript)
    {
        $this->phpScript = $phpScript;
        return $this;
    }

    public function set_identifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function add_arg($arg){
        $this->args[] = escapeshellarg($arg);
        return $this;
    }

    public function set_outputFile($outputFile)
    {
        $this->outputFile = $outputFile;
        return $this;
    }
    public function set_pidFile($pidFile)
    {
        $this->pidFile = $pidFile;
        return $this;
    }

    public function exec()
    {
        if(is_null($this->phpScript))
        {
            throw new Exception('No php script setted');
        }

        exec(sprintf("%s > %s 2>&1 & echo $! >> %s", PHP_BINARY . " " . $this->phpScript . " " . implode(" ", $this->args), $this->outputFile, $this->pidFile));
        
        $this->status ="running";
        $this->pid = $this->get_pid_from_pidfile();
    }

    public function is_running()
    {
        try{
            $result = shell_exec(sprintf("ps %s", $this->pid));
            if( count(preg_split("/\n/", $result)) > 2){
                return true;
            }
        }catch(Exception $e){}
        return false;
    }

    public function stop()
    {
        posix_kill( $this->pid, SIGTERM );
        return $this;
    }

    public function remove_output_file()
    {
        if(file_exists ( $this->outputFile )){
            unlink($this->outputFile);
        }
        return $this;
    }
    
}