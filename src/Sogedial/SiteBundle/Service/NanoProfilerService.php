<?php

// Mesures de performances (avec plusieures étapes)
// Exemple:
//         $prof = $this->get('sogedial.nanoprofiler');
//         $prof->start();
//         do_step_1();
//         $prof->checkpoint("step 1");
//         do_step_2());
//         $prof->checkpoint("step 2");
//         $prof->end_and_die();
//
// Vous pouvez utiliser $prof->end() au lieu de end_and_die() pour ne pas interrompre l'exécution


namespace Sogedial\SiteBundle\Service;

class NanoProfilerService
{
    private $timestamps;
    private $original_timestamp;
    private $last_timestamp;

    private function init()
    {
        $this->timestamps = array();
        $this->original_timestamp = microtime(true);
        $this->last_timestamp = $this->original_timestamp;
    }

    public function __construct()
    {
        $this->init();
    }

    public function start()
    {
        $this->init();
    }

    public function checkpoint($name="")
    {
        $new_timestamp = microtime(true);
        $time_diff = $new_timestamp - $this->last_timestamp;
        $this->last_timestamp = $new_timestamp;
        if ($name === "") {
            $this->timestamps[] = $time_diff;
        } else {
            $this->timestamps[$name] = $time_diff;
        }
    }

    public function end()
    {
        $result="Profiler result:\n";
        foreach ($this->timestamps as $name => $timestamp) {
            $result.='Step "'.$name.'": '.round($timestamp*1000)."ms\n";
        }
        return $result;
    }

    public function end_and_die()
    {
        print("<html><body><pre>".$this->end()."</pre></body></html>");
        die();
    }
}