<?php

namespace UkrSolution\BarcodeScanner\features\Debug;

use UkrSolution\BarcodeScanner\features\settings\Settings;

class Debug
{
    public static $status = null;

    private static $startTime = null;
    private static $startMemory = null;
    private static $lastPointTime = null;
    private static $sumLustPointTime = null;
    private static $points = array();

    public static function init($source = "plugin")
    {
        try {
            if (count(self::$points) > 0) {
                return;
            }

            self::$startTime = microtime(true);
            self::$startMemory = memory_get_peak_usage(true);
            self::$lastPointTime = 0;
            self::$sumLustPointTime = 0;

            if (isset($GLOBALS['usbs_debug_config'])) {
                self::$startTime = $GLOBALS['usbs_debug_config']['startTime'];
                self::$startMemory = $GLOBALS['usbs_debug_config']['startMemory'];
                self::$lastPointTime = $GLOBALS['usbs_debug_config']['lastPointTime'];
                self::$sumLustPointTime = $GLOBALS['usbs_debug_config']['sumLustPointTime'];
            }

            if (isset($GLOBALS['usbs_debug'])) {
                foreach ($GLOBALS['usbs_debug'] as $point) {
                    self::$points[] = $point;
                }
            } 
            else {
                self::addPoint('Start Debug from ' . $source, true);
            }
        } catch (\Throwable $th) {
        }
    }

    public static function addPoint($name = "", $isDebugEnabled = false)
    {
        if (!$isDebugEnabled) {
            return;
        }

        self::$sumLustPointTime += self::$lastPointTime;

        self::$lastPointTime = microtime(true) - self::$startTime - self::$sumLustPointTime;

        self::$points[] = array(
            'name' => $name,
            'totalTime' => microtime(true) - self::$startTime,
            'pointTime' => self::$lastPointTime,
            'pointMemory' => ((memory_get_peak_usage(true) - self::$startMemory) / 1024 / 1024)
        );
    }

    public static function getResult($status = false, $isDebugEnabled = false)
    {
        self::addPoint('Stop Benchmark');

        if ($status) {
        }
        else {
            return null;
        }

        $result = array("points" => array(), "total" => array());

        if ($status) {
            $prevMem = 0;
            $usedTime = 0;

            foreach (self::$points as $key => $value) {
                $result["points"][] = array(
                    "0" => $value['name'],
                    "1" => sprintf('%.4F sec', $value['pointTime']),
                    "2" => sprintf('%.4F sec', $value['totalTime']),
                );

                $prevMem += $value['pointMemory'];
                $usedTime += $value['pointTime'];
            }

            $result["total"] = array(
                "memory used" => $prevMem . 'M / ' . ini_get('memory_limit'),
                "execution time used" => number_format($usedTime, 3, ".", "") . 'sec / ' . ini_get('max_execution_time') . 'sec'
            );
        }

        return $result;
    }
}
