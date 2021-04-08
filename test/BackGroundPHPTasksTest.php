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
       unlink("./testpid.pid");

    }
    public function testRemoveOutputFile()
    {
        $waitTask = new BackgroundPHPTask();
        $waitTask   ->set_phpScript( $this->createTestScriptFile() )
                    ->set_outputFile("./out.txt")
                    ->set_pidFile("./testpid.pid")
                    -> exec();
        $this->assertFileExists("./out.txt");
        $waitTask->stop()->remove_output_file();
        if(method_exists($this,"assertFileDoesNotExist") )
        {
            $this->assertFileDoesNotExist("./out.txt"); //on php 7.2 Travis use phpunit 8 and not 9
        }else{
            $this->assertFalse(file_exists("./out.txt"));
        }
        unlink("./testpid.pid");

        
    }

    public function testManagerQueue()
    {
        $basePath = sys_get_temp_dir()."/BackgroundTasksManager";
        //purge base path
        $files = glob($basePath .'/*'); // get all file names
        foreach($files as $file){ // iterate files
            if(is_file($file)) {
                unlink($file); // delete file
            }
        }

        $script1 = '<?php echo "hey"; sleep(3); ?>';
        $script2 = '<?php echo "ho"; sleep(3); ?>';

        $script1FullName = tempnam( sys_get_temp_dir(), 'testScript');
        $script2FullName = tempnam( sys_get_temp_dir(), 'testScript');

        file_put_contents($script1FullName,$script1);
        file_put_contents($script2FullName,$script2);

        $basePath = sys_get_temp_dir()."/BackgroundTasksManager";
        @mkdir($basePath);
        $taskQueue = new BackgroundTasksManager($basePath);

        $task1ToAdd = new BackgroundPHPTask();
        $task1ToAdd->set_phpScript( $script1FullName );
        $taskQueue->add_task_on_queue($task1ToAdd);

        $task2ToAdd = new BackgroundPHPTask();
        $task2ToAdd->set_phpScript( $script2FullName );
        $taskQueue->add_task_on_queue($task2ToAdd);
        
        $this->assertEquals("running", $task1ToAdd->get_status());
        sleep(4);
        $this->assertEquals("terminated", $task1ToAdd->get_status());

        $taskQueue->check_queue();
        $this->assertEquals("running", $task2ToAdd->get_status());

        $task2ToAdd->stop();
    }

    public function testOutputFiles()
    {
        $basePath = sys_get_temp_dir()."/BackgroundTasksManager";
        //purge base path
        $files = glob($basePath .'/*'); // get all file names
        foreach($files as $file){ // iterate files
            if(is_file($file)) {
                unlink($file); // delete file
            }
        }

        $script1 = '<?php echo "heyscript1hey\nplip";?>';
        $script2 = '<?php echo "hoscript2ho";?>';
        $script3 = '<?php echo "dtufjfj(yu";?>';

        $script1FullName = tempnam( sys_get_temp_dir(), 'testScript');
        $script2FullName = tempnam( sys_get_temp_dir(), 'testScript');
        $script3FullName = tempnam( sys_get_temp_dir(), 'testScript');

        $outputFile1 = tempnam( sys_get_temp_dir(), 'testScript');
        $outputFile2 = tempnam( sys_get_temp_dir(), 'testScript');
        $outputFile3 = tempnam( sys_get_temp_dir(), 'testScript');

        file_put_contents($script1FullName,$script1);
        file_put_contents($script2FullName,$script2);
        file_put_contents($script3FullName,$script3);

        $task1 = new BackgroundPHPTask();
        $task1 ->set_phpScript($script1FullName)
               ->set_outputFile($outputFile1)
               ->set_identifier("task1");

        $task2 = new BackgroundPHPTask();
        $task2 ->set_phpScript($script2FullName)
                ->set_outputFile($outputFile2)
                ->set_identifier("task2");
        $task3 = new BackgroundPHPTask();
        $task3 ->set_phpScript($script3FullName)
                ->set_outputFile($outputFile3)
                ->set_identifier("task3");
        
        $taskQueue = new BackgroundTasksManager($basePath);
        $taskQueue ->add_task_on_queue($task1)
                    ->add_task_on_queue($task2)
                    ->add_task_on_queue($task3);

        sleep(1);
        $this->assertFalse(strrpos(file_get_contents ($outputFile1),"heyscript1hey") === false);
        $taskQueue ->check_queue();
        sleep(1);
        $this->assertFalse(strrpos(file_get_contents ($outputFile2),"hoscript2ho") === false);
        $taskQueue ->check_queue();
        sleep(1);
        $this->assertFalse(strrpos(file_get_contents ($outputFile3),"dtufjfj(yu") === false);

    }
    public function testRemoveAll()
    {
        $basePath = sys_get_temp_dir()."/BackgroundTasksManager";
        $taskQueue = new BackgroundTasksManager($basePath);
        $taskQueue -> stop_and_remove();

        if(method_exists($this,"assertFileDoesNotExist") ){
            $this->assertFileDoesNotExist(sys_get_temp_dir()."/BackgroundTasksManager/TaskManager.pid"); 
        }else{
            $this->assertFalse(file_exists("/BackgroundTasksManager/TaskManager.pid"));
        }

        if(method_exists($this,"assertFileDoesNotExist") ){
            $this->assertFileDoesNotExist(sys_get_temp_dir()."/BackgroundTasksManager/TaskManager.serialized"); 
        }else{
            $this->assertFalse(file_exists("/BackgroundTasksManager/TaskManager.serialized"));
        }



    }
    public function testDaemon()
    {
        $fileOut = tempnam( sys_get_temp_dir(), 'out');
        $script1 = '<?php file_put_contents("' . $fileOuT .'", "first",FILE_APPEND); sleep(2);';
        $script2 = '<?php file_put_contents("' . $fileOuT .'", "second",FILE_APPEND); sleep(2);';
        $script3 = '<?php file_put_contents("' . $fileOuT .'", "third",FILE_APPEND); sleep(2);';
    }

}