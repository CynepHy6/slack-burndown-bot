<?php


namespace App;


use CpChart\Data;
use CpChart\Image;

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

    public function build(): string
    {
        $targetLine = [
            ['x' => $this->reader->sprint_start, 'y' => $this->reader->startEstimation()],
            ['x' => $this->reader->sprint_end, 'y' => 0],
        ];
        $lastEntry = time();
        $lastEntry = $lastEntry > $this->reader->sprint_end ? $this->reader->sprint_end : $lastEntry;

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
            ['name' => 'Target', 'color' => '#959595', 'data' => $targetLine],
            ['name' => 'Logged', 'color' => '#10cd10', 'data' => $loggedLine],
            ['name' => 'Real', 'color' => '#cd1010', 'data' => $realLine],
        ];

        return $this->createChart($lines);
    }

    public function createChart(array $lines): string
    {
        /* Build a dataset */
        $data = new Data();
        foreach ($lines as $chartData) {
            $points = Utils::flatten($chartData['data'], 'y');
            $data->addPoints($points, $chartData['name']);
            //            $data->setSerieTicks("Probe 2", 4);
            //            $data->setSerieWeight("Probe 3", 2);
            $data->setAxisName(0, 'Axis 0');
            $data->addPoints(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'], 'Labels');
            $data->setSerieDescription('Labels', 'Months');
            $data->setAbscissa('Labels');
        }

        /* Create the 1st chart */
        $width = 1000;
        $height = 900;
        $pad = 70;
        $image = new Image($width, $height, $data);
        $image->setGraphArea($pad, $pad, $width - $pad, $height - $pad);
        $image->drawFilledRectangle($pad, $pad, $width - $pad, $height - $pad, [
            'R'           => 255,
            'G'           => 255,
            'B'           => 255,
            'Surrounding' => -200,
            'Alpha'       => 10,
        ]);
        $image->drawScale(['DrawSubTicks' => true]);
        $image->setShadow(true, ['X' => 1, 'Y' => 1, 'R' => 0, 'G' => 0, 'B' => 0, 'Alpha' => 10]);
        $image->setFontProperties(['FontSize' => 6]);
        $image->drawLineChart(['DisplayValues' => true, 'DisplayColor' => DISPLAY_AUTO]);
        $image->setShadow(false);

        /* Write the legend */
        $image->drawLegend(510, 205, ['Style' => LEGEND_NOBORDER, 'Mode' => LEGEND_HORIZONTAL]);
        $fileName = 'example.drawLineChart.png';
        $image->autoOutput($fileName);
        return $fileName;
    }
}
