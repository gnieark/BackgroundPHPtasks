<?php
class BackgroundPHPTask
{
  
    /**
     * The BackgroundPHPTask class.
     *
     * @category Process
     * @package  BackgroundPHPTask
     * @author   gnieark <gnieark@tinad.fr>
     * @license  GNU General Public License V3
     * @link     https://github.com/gnieark/BackgroundPHPtasks
     */

    /**
     * The full path of the pid file to use or create.
     */

    private string $pidFile = "";

    /**
     * the pid id, will be populated after exec.
     */

    private int $pid;

    /**
     * Where is stored the script output.
     */

    private string $outputFile = "/dev/null";

    /**
     * The php's script to execute full path + name .
     */

    private string $phpScript;

    /**
     * Args to pass to the script when called. /!\Shell arguments, not query args $_GET 
     * 
     */

    private $args = array();


    /**
    * Store the status
    * 
    */


    private string $status = "pending"; //should be pending/running/terminated

    /**
     * Use it as title, unique-key, as you want
     *
     */

    private string $identifier =""; //not the PID, juste another identifier if needed

    /**
     * Return the status, Eventually check for a change before
     *
     */


    public function get_status():string
    {
        if( ( $this->status == "running" ) && (!$this->is_running()) )
        {
            $this->status = "terminated";
        }
        return $this->status;
    }

    /**
     * Return the pid (normaly useless,for debug)
     *
     */

    public function get_pid():int
    {
        return $this->pid;
    }

    /**
     * Return the pid file path (normaly useless,for debug)
     *
     */
 
    public function get_pifFile():string
    {
        return $this->pidFile;
    }

    /**
     * Return the php script file path (normaly useless,for debug)
     *
     */

    public function get_phpScript():string
    {
        return $this->phpScript;
    }

    /**
     * Return the last pid on a pid file
     *
     */

    private function get_pid_from_pidfile():int
    {
        
        $data = file($this->pidFile);
        return intval( $data[count($data) -1] );
    }

    /**
     * Set the php script to use
     *
     * @return BackgroundPHPTask for chaining
     * 
     * @param $phpScript the path
     */

    public function set_phpScript(string $phpScript):BackgroundPHPTask
    {
        $this->phpScript = $phpScript;
        return $this;
    }

    /**
     * Set the php script to use, but by given the whole script on a string
     *
     * @return BackgroundPHPTask for chaining
     * 
     * @param $script the script. containing the opening bracket <?php
     */

    public function set_phpScriptWithoutFile(string $script):BackgroundPHPTask
    {
        $scriptPath= tempnam(sys_get_temp_dir(), 'BackgroundPhpTask');
        file_put_contents($scriptPath, $script);
        return $this->set_phpScript($scriptPath);
    }
   /**
     * Set an identifier (optional). Should be usefull for your Deus Ex
     * Machina
     * @return BackgroundPHPTask for chaining
     * 
     * @param string $identifier, what you want
     */
    public function set_identifier(string $identifier):BackgroundPHPTask
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * add an argument to give to the script
     * 
     * @return BackgroundPHPTask for chaining
     * 
     * @param string $arg, what you want
     */

    public function add_arg(string $arg):BackgroundPHPTask{
        $this->args[] = escapeshellarg($arg);
        return $this;
    }

    /**
     * Set where your scripts outputs are going.
     * If none given, default is /dev/null
     * 
     * @return BackgroundPHPTask for chaining
     * 
     * @param string Path of output file (will be created if not yet existing)
     */

    public function set_outputFile(string $outputFile):BackgroundPHPTask
    {
        $this->outputFile = $outputFile;
        return $this;
    }

    /**
     * Set the pid file where the process id will be stored
     * 
     * 
     * @return BackgroundPHPTask for chaining
     * 
     * @param string Path of pid file (will be created if not yet existing)
     */

    public function set_pidFile(string $pidFile):BackgroundPHPTask
    {
        $this->pidFile = $pidFile;
        return $this;
    }

    /**
     * Launch the script execution
     * 
     * @return BackgroundPHPTask for chaining
     * 
     */


    public function exec():void
    {
        if(is_null($this->phpScript))
        {
            throw new Exception('No php script setted');
        }

        exec(sprintf("%s > %s 2>&1 & echo $! >> %s", PHP_BINARY . " " . $this->phpScript . " " . implode(" ", $this->args), $this->outputFile, $this->pidFile));
        
        $this->status ="running";
        $this->pid = $this->get_pid_from_pidfile();
    }

    /**
     * test if a script is running (using his pid)
     * 
     * @return bool
     */

    public function is_running():boolean
    {
        try{
            $result = shell_exec(sprintf("ps %s", $this->pid));
            if( count(preg_split("/\n/", $result)) > 2){
                return true;
            }
        }catch(Exception $e){}
        return false;
    }

    /**
     * Kill the current process if running
     * 
     * @return BackgroundPHPTask for chaining
     * 
     */

    public function stop():BackgroundPHPTask
    {
        posix_kill( $this->pid, SIGTERM );
        return $this;
    }

    /**
     * Delete the current process output file, if you need to make cleaness
     * 
     * @return BackgroundPHPTask for chaining
     * 
     */

    public function remove_output_file():BackgroundPHPTask
    {
        if(file_exists ( $this->outputFile )){
            unlink($this->outputFile);
        }
        return $this;
    }
    
}