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
     * @var array
     */
    private $rates;

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
        $this->rates = $this->json['workRateData']['rates'] ?? []; // TODO: rates

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
            $dataItem = array_filter($item, static function ($it) {
                return !empty($it['timeC']);
            });
            return count($dataItem) > 0;
        });

        $res = [];
        foreach ($data as $key => $val) {
            $timeChanges = array_filter($val, static function ($val) {
                return !empty($val['timeC']);
            });
            $res[] = [date('Y-m-d H:i:s', (int) ($key / 1000)), $timeChanges];
        }
        return $res;
    }

    public function startEstimation(): int
    {
        $data = array_filter($this->jsonChangesByDateTime(), function ($item) {
            return $item[0] <= $this->sprintStart();
        });
        $estimateData = Utils::flatten($data, 'oldEstimate');
        $res = array_reduce($estimateData, static function ($sum, $num) {
            if ($num > 0) {
                return $sum + $num;
            }
            return $sum;
        });
        return $res;
    }

    public function changesDuringSprint(): array
    {
        $data = array_filter($this->jsonChangesByDateTime(), function ($val) {
            return $val[0] > $this->sprintStart() && $val[0] < $this->sprintEnd();
        });
        $data = array_map(static function ($item) {
            [$key, $value] = $item;
            $durationChange = array_reduce($value, static function ($sum, $val) {
                $old = (int) $val['timeC']['oldEstimate'];
                $new = (int) $val['timeC']['newEstimate'];
                return $sum - ($old - $new);
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

        $data = array_map(static function ($item) {
            [$key, $value] = $item;
            $timeSpent = array_reduce($value, static function ($sum, $val) {
                if (isset($val['timeC']['timeSpent'])) {
                    $spent = (int) $val['timeC']['timeSpent'];
                    return $sum + $spent;
                }
                return $sum;
            });
            return [$key, $timeSpent];
        }, $data);
        return $data;
    }
}
