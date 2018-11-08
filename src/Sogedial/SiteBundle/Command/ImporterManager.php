<?php
namespace Sogedial\SiteBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;



class ImporterManager extends ContainerAwareCommand 
{
    //Associer la code numerique a chaque region dans le bonne ordre
    private static $regionAll = ["region1" => 1, "region2" => 2, "region3" => 3, "region4" => 4];
    //db access
    protected static $em;
    //multisite access
    protected static $ms;
    //recherche access
    protected static $srch;
    //offset progression
    protected static $batchSize = 20;
    // label command
    protected $labelExecution;
    protected $output;
    protected $input;
    protected $region;
    protected $regionNumeric;
    // skipped lines
    protected $skipped;
    protected $societe_site;
    /**
    * print label execution
    */
    private function displayExecutionHeader($labelHeader)
    {
        $now = new \DateTime();
        $this->output->writeln('<comment>' . $labelHeader . ' (' . $this->labelExecution . ') : ' . $now->format('d-m-Y G:i:s') . ' ---</comment>');
    }

    protected function displayStartHeader(){
        $this->displayExecutionHeader('Start');
    }

    protected function displayFinishHeader()
    {
        $this->progress->finish();
        $this->output->writeln('');
        if($this->skipped !== NULL && $this->skipped > -1){
            $this->output->writeln("$this->skipped $this->labelExecution entries skipped.");        
        }
        $this->displayExecutionHeader('End');
    }

    protected function executeLoopCmd($command, $commandeName, $input, $output){
        self::setup($input, $output, $commandeName);
        $data = $this->get($input, $output);
        self::initProgress(count($data));
        $this->displayStartHeader();

        if ($data === false) {
            $output->writeln('File not found, skipping.');
            $this->setSkipped(-1);
        } else {
            $this->skipped = $command->import($data, $output);
            $this->finish();
        }

        $this->displayFinishHeader();
    }

    protected function executeCmd($command, $commandeName, $input, $output)
    {
        if($this->getRegion() === NULL || $this->getRegion() === 0){
            $regions = $input->getArgument('regions');
            if(count($regions) === 0 ){
                $regions = $this->getRegionAll();
            } else {
                $regionsLen = count($regions);
                $regionsAux = array();
                for($i = 0; $i < $regionsLen; $i++){
                    $regionsAux[$regions[$i]] = substr($regions[$i], -1);
                }
                $regions = $regionsAux;
            }
            foreach($regions as $region => $regionId){
                $output->write("REGION: $region\n");
                $this->setRegion($region);
                $this->setRegionNumeric($regionId);
                $this->executeLoopCmd($command, $commandeName, $input, $output);
            }
        } else {
              $this->executeLoopCmd($command, $commandeName, $input, $output);
        }
    }

    protected function configureCmd($name, $description)
    {
        $this
            ->setName($name)
            ->setDescription($description)
            ->addArgument(
                'regions',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'multiples'
            );
    }


    /**
    * @param time string between 0 and 6 characters
    */
    protected function setDateTime($date, $time = ""){
        $d = new \DateTime($date);

        $t = sprintf("%'.06d", $time);
        $h = intval(substr($t, 0, 2));
        $m = intval(substr($t, 2, 2));
        $s = intval(substr($t, 4, 2));
        $d->setTime($h, $m, $s);
        return $d;
    }

    protected function finish(){
        self::$em->flush();
        self::$em->clear();
        gc_enable();
        gc_collect_cycles();
    }

    protected function advance($i, $output)
    {
        if (($i % self::$batchSize) === 0) {
            self::$em->flush();
            self::$em->clear();
            gc_enable();
            gc_collect_cycles();

            $this->progress->advance(self::$batchSize);

            $now = new \DateTime();
            $this->output->write(" of $this->labelExecution imported ... | " . $now->format('d-m-Y G:i:s'));
        }
    }

    /**
    * variable de commandes
    */
    protected function setup(InputInterface $input, OutputInterface $output, $labelExecution)
    {
        $this->setLabelExecution($labelExecution);
        $this->setInput($input);
        $this->setOutput($output);
        $this->initEm();
        $this->initMs();
        $this->initSrch();
        $this->skipped = 0;
    }

    protected function initProgress($size)
    {
        $this->progress = new ProgressBar($this->output, $size);
    }

    private function initEm()
    {
        if(!isset(self::$em)){
            self::$em = $this->getContainer()->get('doctrine')->getManager();
            self::$em->getConnection()->getConfiguration()->setSQLLogger(null);
        }
    }

    private function initMs()
    {
        if(!isset(self::$ms)){
            self::$ms = $this->getContainer()->get('sogedial.multisite');
            $this->societe_site = self::$ms->getSociete();
        }
    }

    private function initSrch(){
        if(!isset(self::$srch)){
            self::$srch = $this->getContainer()->get('sogedial.recherche');
        }
    }

    public function setOutput($output)
    {
        $this->output = $output;       
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function setInput($input)
    {
        $this->input = $input;       
    }

    public function getInput()
    {
        return $this->input;
    }

    public function setLabelExecution($labelExecution)
    {
        $this->labelExecution = $labelExecution;
    }

    public function getLabelExecution()
    {
        return $this->labelExecution;
    }

    public function setSkipped($skipped)
    {
        $this->skipped = $skipped;
    }

    public function getSkipped()
    {
        return $this->skipped;
    }

    public function setRegion($region)
    {
        $this->region = $region;
    }

    public function getRegion()
    {
        return $this->region;
    }

    public function setRegionNumeric($regionNumeric)
    {
        $this->regionNumeric = $regionNumeric;
    }

    public function getRegionNumeric()
    {
        return $this->regionNumeric;
    }

    public function getRegionAll(){
        return self::$regionAll;
    }

    public function getRegionAllNumeric(){
        return self::$regionAllNumeric;
    }

    public function getSocieteSite(){
        return $this->societe_site;
    }

    function printMemoryUsage()
    {
        $this->output->writeln(sprintf('Memory usage (currently) %dKB/ (max) %dKB', round(memory_get_usage(true) / 1024), memory_get_peak_usage(true) / 1024));
    }
}
