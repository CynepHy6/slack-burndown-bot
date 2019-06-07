<?php


namespace App;


class SprintJsonReader
{
    /**
     * @var array
     */
    private $json;
    /**
     * @var int
     */
    public $sprint_start;
    /**
     * @var int
     */
    public $sprint_end;

    /**
     * SprintJsonReader constructor.
     *
     * @param $json
     */
    public function __construct($json)
    {
        $this->json = json_decode($json, true);
        $this->sprint_start = (int) ($this->json['startTime'] / 1000);
        $this->sprint_end = (int) ($this->json['endTime'] / 1000);

    }

    public function sprintStart()
    {
        return date('Y-m-d H:i:s', $this->sprint_start);
    }

    public function sprintEnd()
    {
        return date('Y-m-d H:i:s', $this->sprint_end);
    }

    public function jsonChangesByDateTime(): array
    {
        $data = array_filter($this->json['changes'], static function ($item) {
            $dataItem = array_filter($item, static function ($item) {
                return !empty($item['timeC']);
            });
            return count($dataItem) > 0;
        });
        $res = [];
        foreach ($data as $key => $val) {
            $timeChanges = array_filter($val, static function ($item) {
                return !empty($item['timeC']);
            });
            $res[] = [date('Y-m-d H:i:s', (int) ($key / 1000)), $timeChanges];
        }
        return $res;
    }

    public function startEstimation(): int
    {
        $data = [];
        foreach ($this->jsonChangesByDateTime() as $item) {
            foreach ($item as $key => $val) {
                if ($key < $this->sprintStart()) {
                    $data[] = [$key => $val];
                }
            }
        }
        $data = Utils::flatten($data, 'newEstimate');
        return array_sum($data);
    }

    public function changesDuringSprint(): array
    {
        $data = array_filter($this->jsonChangesByDateTime(), function ($val) {
            return $val[0] > $this->sprintStart() && $val[0] < $this->sprintEnd();
        });
        $data = array_map(static function ($item) {
            [$key, $value] = $item;
            $durationChange = array_reduce($value, static function ($sum, $val) {
                $sum -= ((int) $val['timeC']['oldEstimate'] - (int) $val['timeC']['newEstimate']);
                return $sum;

            }, 0);
            return [$key, $durationChange];
        }, $data);
        $data = array_filter($data, static function ($val) {
            return $val[1] !== 0;
        });
        return $data;
    }

    public function loggedTimeInSprint(): array
    {
        $data = array_filter($this->jsonChangesByDateTime(), function ($val) {
            return $val[0] > $this->sprintStart() && $val[0] < $this->sprintEnd();
        });

        $data = array_map(static function($item){
            [$key, $value] = $item;
            $timeSpent = array_reduce($value, static function($sum, $val){
                if (isset($val['timeC']['timeSpent'])) {
                    $sum += (int) $val['timeC']['timeSpent'];
                }
                return $sum;
            });
            return [$key, $timeSpent];
        }, $data);
        return $data;
    }
}
