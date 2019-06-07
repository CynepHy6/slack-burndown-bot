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

    /**
     * @param $imgDir
     * converted function from Ruby to PHP
     * https://github.com/vossim/dashing-jira-burndown/blob/master/jobs/jira_burndown.rb#buildBurnDown
     *
     * @return string
     */
    public function build($imgDir): string
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
            'data'   => [
                ['name' => 'Target', 'data' => $targetLine],
                ['name' => 'Logged', 'data' => $loggedLine],
                ['name' => 'Real', 'data' => $realLine],
            ],
            'common' => [],
        ];
        $lines = $this->prepareData($lines);

        return $this->createChart($lines, $imgDir);
    }

    public function createChart(array $lines, string $imgDir): string
    {
        /* Build a dataset */
        $data = new Data();
        foreach ($lines['data'] as $chartData) {
            $data->addPoints($chartData['data'], $chartData['name']);
        }
        $data->setAxisName(0, 'Time spent');
        $data->addPoints($lines['common'], 'Labels');
        $data->setSerieDescription('Labels', 'Date');
        $data->setAbscissa('Labels');
        $data->setAbscissaName('Date record');

        /* Create the 1st chart */
        $width = 1000;
        $height = 900;
        $pad = 80;
        $image = new Image($width, $height, $data);
        $image->setGraphArea($pad, $pad, $width - $pad, $height - $pad);
        $image->drawFilledRectangle($pad, $pad, $width - $pad, $height - $pad, [
            'R'           => 255,
            'G'           => 255,
            'B'           => 255,
            'Surrounding' => -200,
            'Alpha'       => 10,
        ]);
        $image->drawScale(['DrawSubTicks' => false]);
        $image->setShadow(true, ['X' => 1, 'Y' => 1, 'R' => 0, 'G' => 0, 'B' => 0, 'Alpha' => 10]);
        $image->setFontProperties(['FontSize' => 10]);
        $image->drawLineChart(['DisplayValues' => false, 'DisplayColor' => DISPLAY_AUTO]);
        $image->setShadow(false);

        /* Write the legend */
        $image->drawLegend($width / 2 - 100, $height / 4 - 100,
            ['Style' => LEGEND_NOBORDER, 'Mode' => LEGEND_HORIZONTAL]);
        $fileName = $imgDir . '/chart' . time() . '.png';
        //        $image->autoOutput($fileName);
        $image->render($fileName);
        return $fileName;
    }

    private function prepareData(array $lines)
    {
        $common = [];
        foreach ($lines['data'] as $line) {
            foreach ($line['data'] as $item) {
                if (!isset($common[$item['x']])) {
                    $common[$item['x']] = false;
                }
            }
        }
        $data = [];
        ksort($common, SORT_NUMERIC);
        //        Utils::log($common);

        foreach ($lines['data'] as $line) {
            $commonCopy = $common;
            foreach ($line['data'] as $item) {
                $commonCopy[$item['x']] = $item['y'] / 60;
            }
            $line['data'] = array_values($commonCopy);
            $data[] = $line;
        }
        $lines['data'] = $data;
        $lines['common'] = $common;
        return $lines;
    }
}
