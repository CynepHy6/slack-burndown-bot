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
        $this->sprint_end = (int) ($this->json['startTime'] / 1000);

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
        $res = 0;
        $data = array_filter($this->jsonChangesByDateTime(), function ($key, $val) {
            return $key < $this->sprintStart();
        }, ARRAY_FILTER_USE_BOTH);
        var_dump($data);
        return $res;
    }
    /*
    class SprintJsonReader
      def startEstimation
        jsonChangesByDateTime.find_all { |key, value|
          key < sprintStart
        }.flat_map { |key, value|
          value.map { |singleStory|
            [key, singleStory]
          }
        }.reverse_each.reduce([]) {|hash, entry|
          containsItem = hash.find{|tempEntry|
            tempEntry[1]["key"] == entry[1]["key"]
          }
          if containsItem.nil?
            hash.push([entry[0], entry[1]])
          else
            hash
          end
        }.reduce(0) {|estimation, entry|
          estimation + entry[1]["timeC"]["newEstimate"].to_i
        }
      end

      def changesDuringSprint
        jsonChangesByDateTime.find_all { |key, value|
          key > sprintStart && key < sprintEnd
        }.map { |key, value|
          durationChange = value.reduce(0) {|res, story|
            res - (story["timeC"]["oldEstimate"].to_i - story["timeC"]["newEstimate"].to_i)
          }
          [key, durationChange]
        }.find_all { |key, value|
          value != 0
        }
      end

      def loggedTimeInSprint
        jsonChangesByDateTime.find_all { |key, value|
          key > sprintStart && key < sprintEnd
        }.map { |key, value|
          timeSpent = value.reduce(0) {|res, story|
            res + story["timeC"]["timeSpent"].to_i
          }
          [key, timeSpent]
        }
      end
    end
     */
}
