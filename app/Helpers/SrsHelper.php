<?php

namespace App\Helpers;

use Process;

class SrsHelper
{
    public static function transformCoordinate(string $s_srs, string $t_srs, $x, $y): array
    {
        $cmd = "echo \"$x $y\"";
        $cmd .= " | " . config('tool.gdaltransform_path');
        $cmd .= " -s_srs $s_srs -t_srs $t_srs -output_xy";

        $process = Process::run($cmd);
        $output = $process->output();
        if ($process->failed()) {
            throw new \Exception($output);
        }
        $output = preg_split('/\s+/', $output);
        $output = array_values(array_filter($output, function ($axis) {
            if (empty($axis)) {
                return false;
            }
            return true;
        }));
        $output = array_map(function ($axis) {
            return (float) $axis;
        }, $output);

        return $output;
    }
}
