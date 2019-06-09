<?php


namespace App;


use Amenadiel\JpGraph\Graph;
use Amenadiel\JpGraph\Plot;

class BurnDownBuilder
{
    private $reader;

    /**
     * BurnDownBuilder constructor.
     *
     * @param SprintJsonReader $reader
     */
    public function __construct(SprintJsonReader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @param $imgDir
     * converted function from Ruby to PHP
     * https://github.com/vossim/dashing-jira-burndown/blob/master/jobs/jira_burndown.rb#buildBurnDown
     *
     * @return string
     */
    public function build($imgDir): string
    {
        // Target
        $targetLine = [
            ['x' => $this->reader->sprint_start, 'y' => $this->reader->startEstimation()],
            ['x' => $this->reader->sprint_end, 'y' => 0],
        ];
        $lastEntry = time();
        $lastEntry = $lastEntry > $this->reader->sprint_end ? $this->reader->sprint_end : $lastEntry;

        // Real
        $realLine = [
            ['x' => $this->reader->sprint_start, 'y' => $this->reader->startEstimation()],
        ];
        $realLine = array_reduce($this->reader->changesDuringSprint(), static function ($res, $entry) {
            $last = end($res);
            $beforeChange = $last['y'];
            $afterChange = $beforeChange + $entry[1];
            $res[] = ['x' => strtotime($entry[0]), 'y' => $beforeChange];
            $res[] = ['x' => strtotime($entry[0]) + 1, 'y' => $afterChange];
            return $res;
        }, $realLine);
        $last = end($realLine);
        $realLine[] = ['x' => $lastEntry, 'y' => $last['y']];

        // Logged
        $loggedLine = [
            ['x' => $this->reader->sprint_start, 'y' => 0],
        ];
        $loggedLine = array_reduce($this->reader->loggedTimeInSprint(), static function ($res, $entry) {
            $last = end($res);
            $beforeChange = $last['y'];
            $afterChange = $beforeChange + $entry[1];
            $res[] = ['x' => strtotime($entry[0]), 'y' => $beforeChange];
            $res[] = ['x' => strtotime($entry[0]) + 1, 'y' => $afterChange];
            return $res;
        }, $loggedLine);
        $last = end($loggedLine);
        $loggedLine[] = ['x' => $lastEntry, 'y' => $last['y']];

        $lines = [
            'data'   => [
                ['name' => 'Target', 'color' => 'navy', 'data' => $targetLine],
                ['name' => 'Logged', 'color' => 'red', 'data' => $loggedLine],
                ['name' => 'Real', 'color' => 'orange', 'data' => $realLine],
            ],
            'common' => [],
        ];
        //        $lines = $this->prepareData($lines);

        //        return $this->createChart($lines, $imgDir);
        return $this->createChart($lines, $imgDir);
    }

    public function createChart(array $lines, string $imgDir): string
    {
        // Setup the graph
        $width = 1000;
        $height = 900;
        $xpad = 50;
        $ypad = 10;
        $title = 'Sprint burndown';

        $graph = new Graph\Graph($width, $height);
        $graph->SetScale('datint'); // x-axis: 'dat'e, y-axis: 'int'eger
        $graph->SetMargin($xpad, $xpad, $ypad, $ypad);
        $graph->title->Set($title);
        $graph->ygrid->SetFill(true, '#EFEFEF@0.5', '#BBCCFF@0.5'); // фон полотна

        $graph->legend->SetShadow('gray@0.4', 5);
        $graph->legend->SetPos(0.7, 0.05);


        // Setup the callback and adjust the angle of the labels
        $graph->xaxis->SetLabelFormatCallback(static function ($xval) {
            return date('m-d H:i', $xval);
        });
        $graph->xaxis->SetLabelAngle(90);
        // Set the labels every $interval seconds
        $interval = 24 * 3600;
        $graph->xaxis->scale->ticks->Set($interval);

        // Create lines
        foreach ($lines['data'] as $line) {
            $datay = Utils::flatten($line['data'], 'y');
            $datay = array_map(static function ($y) {
                return $y / 3600;
            }, $datay);
            $datax = Utils::flatten($line['data'], 'x');
            $plot = new Plot\LinePlot($datay, $datax);
            $plot->SetColor($line['color']);
            $plot->SetLegend($line['name']);
            $graph->Add($plot);
        }

        // Output line
        $imgName = $this->generateImgName($imgDir);
        $graph->Stroke($imgName);
        return $imgName;
    }

    private function generateImgName(string $imgDir): string
    {
        return $imgDir . '/chart' . time() . '.png';
    }
}
