<?php
use PHPUnit\Framework\TestCase;

class BackGroundPHPTasksTest extends TestCase
{
    private $testScript = '<?php
    while( 1 == 1){
        if(isset($argv[1]))
        {
            foreach ($argv as $arg)
            {
                echo strrev("$arg");
            }
        }else{
            echo date("Y-m-d H:i:s");
            sleep(1);
        }
    }';
    private function createTestScriptFile()
    {
        $scriptFullName = tempnam(sys_get_temp_dir(), 'testScript');
        file_put_contents($scriptFullName, $this->testScript);
        return $scriptFullName;
    }
    public function testTask()
    {
        $waitTask = new BackgroundPHPTask();
        $waitTask   ->set_phpScript( $this->createTestScriptFile() )
                    ->set_outputFile("./out.txt")
                    ->set_pidFile("./testpid.pid")
                    -> exec();

        $this->assertFileExists("./testpid.pid");
        $this->assertFileExists("./out.txt");
        $this->assertTrue($waitTask->is_running());
        $this->assertFalse($waitTask->stop()->is_running());

       unlink("./testpid.pid");
       unlink("./out.txt"); 
    }
    public function testWithNoScriptGiven()
    {
        $this->expectException(Exception::class);
        $task = new BackgroundPHPTask();
        $task->exec();
    } 
    public function testTaskWithArgs()
    {
        $task = new BackgroundPHPTask();
        $outputFile = tempnam(sys_get_temp_dir(), 'out');
        $task   ->set_phpScript( $this->createTestScriptFile() )
                    ->set_outputFile( $outputFile )
                    ->set_pidFile("./testpid.pid")
                    ->add_arg("chocolatine")
                    ->add_arg("pain au chocolat")
                    -> exec();
        sleep(1);
        $task->stop();

        $this->assertFileExists( $outputFile);
        $output = file_get_contents( $outputFile );
        
        $this->assertFalse(strrpos($output,"enitalocohc") === false);
        $this->assertFalse(strrpos($output,"talocohc ua niap") === false);
        
        unlink("./testpid.pid");
        unlink($outputFile); 
    }

    public function testStatusChanges()
    {
        $task = new BackgroundPHPTask();
        $this->assertEquals("pending", $task->get_status());
        $outputFile = tempnam(sys_get_temp_dir(), 'out');
        $task   ->set_phpScript( $this->createTestScriptFile() )
                    ->set_outputFile( $outputFile )
                    ->set_pidFile("./testpid.pid")
                    ->add_arg("chocolatine")
                    ->add_arg("pain au chocolat")
                    -> exec();
        
        $this->assertEquals("running", $task->get_status());

        $task->stop();
        $this->assertEquals("terminated", $task->get_status());


        //test with a task that will be finished quickly
       $task = new BackgroundPHPTask();
       $script = '<?php echo 1; ?>';
       $scriptFullName = tempnam(sys_get_temp_dir(), 'testScript');
       file_put_contents($scriptFullName, $script);
       $task   ->set_phpScript( $scriptFullName )
       ->set_outputFile( $outputFile )
       ->set_pidFile("./testpid.pid")
       -> exec();
       sleep(1);
       $this->assertEquals("terminated", $task->get_status());

    }

}
