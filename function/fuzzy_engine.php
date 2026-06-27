<?php
// function/fuzzy_engine.php - Core Fuzzy Mamdani Engine

class FuzzyMamdani {
    
    // 1. Fuzzifikasi: Derajat Keanggotaan UAN (0-40)
    public static function uan($x) {
        $rendah = 0;
        $sedang = 0;
        $tinggi = 0;

        // Rendah (0 - 10 - 20)
        if ($x <= 10) {
            $rendah = 1.0;
        } elseif ($x > 10 && $x < 20) {
            $rendah = (20.0 - $x) / 10.0;
        } else {
            $rendah = 0.0;
        }

        // Sedang (15 - 22 - 29)
        if ($x <= 15 || $x >= 29) {
            $sedang = 0.0;
        } elseif ($x > 15 && $x <= 22) {
            $sedang = ($x - 15.0) / 7.0;
        } elseif ($x > 22 && $x < 29) {
            $sedang = (29.0 - $x) / 7.0;
        }

        // Tinggi (25 - 35 - 40)
        if ($x <= 25) {
            $tinggi = 0.0;
        } elseif ($x > 25 && $x < 35) {
            $tinggi = ($x - 25.0) / 10.0;
        } else {
            $tinggi = 1.0;
        }

        return [
            'Rendah' => round($rendah, 4),
            'Sedang' => round($sedang, 4),
            'Tinggi' => round($tinggi, 4)
        ];
    }

    // Fuzzifikasi: Derajat Keanggotaan Raport (0-100)
    public static function raport($x) {
        $rendah = 0;
        $sedang = 0;
        $tinggi = 0;

        // Rendah (0 - 55 - 65)
        if ($x <= 55) {
            $rendah = 1.0;
        } elseif ($x > 55 && $x < 65) {
            $rendah = (65.0 - $x) / 10.0;
        } else {
            $rendah = 0.0;
        }

        // Sedang (60 - 72.5 - 85)
        if ($x <= 60 || $x >= 85) {
            $sedang = 0.0;
        } elseif ($x > 60 && $x <= 72.5) {
            $sedang = ($x - 60.0) / 12.5;
        } elseif ($x > 72.5 && $x < 85) {
            $sedang = (85.0 - $x) / 12.5;
        }

        // Tinggi (75 - 88 - 100)
        if ($x <= 75) {
            $tinggi = 0.0;
        } elseif ($x > 75 && $x < 88) {
            $tinggi = ($x - 75.0) / 13.0;
        } else {
            $tinggi = 1.0;
        }

        return [
            'Rendah' => round($rendah, 4),
            'Sedang' => round($sedang, 4),
            'Tinggi' => round($tinggi, 4)
        ];
    }

    // Fuzzifikasi: Derajat Keanggotaan Tes Kompetensi (0-100)
    public static function kompetensi($x) {
        $rendah = 0;
        $sedang = 0;
        $tinggi = 0;

        // Rendah (0 - 50 - 60)
        if ($x <= 50) {
            $rendah = 1.0;
        } elseif ($x > 50 && $x < 60) {
            $rendah = (60.0 - $x) / 10.0;
        } else {
            $rendah = 0.0;
        }

        // Sedang (55 - 67.5 - 80)
        if ($x <= 55 || $x >= 80) {
            $sedang = 0.0;
        } elseif ($x > 55 && $x <= 67.5) {
            $sedang = ($x - 55.0) / 12.5;
        } elseif ($x > 67.5 && $x < 80) {
            $sedang = (80.0 - $x) / 12.5;
        }

        // Tinggi (75 - 88 - 100)
        if ($x <= 75) {
            $tinggi = 0.0;
        } elseif ($x > 75 && $x < 88) {
            $tinggi = ($x - 75.0) / 13.0;
        } else {
            $tinggi = 1.0;
        }

        return [
            'Rendah' => round($rendah, 4),
            'Sedang' => round($sedang, 4),
            'Tinggi' => round($tinggi, 4)
        ];
    }

    // Output Kelulusan: Tidak Lulus (0 - 52 - 70)
    public static function mu_tidak_lulus($z) {
        if ($z <= 52) {
            return 1.0;
        } elseif ($z > 52 && $z < 70) {
            return (70.0 - $z) / 18.0;
        } else {
            return 0.0;
        }
    }

    // Output Kelulusan: Lulus (60 - 75 - 100)
    public static function mu_lulus($z) {
        if ($z <= 60) {
            return 0.0;
        } elseif ($z > 60 && $z < 75) {
            return ($z - 60.0) / 15.0;
        } else {
            return 1.0;
        }
    }

    // 2. Inferensi: Evaluasi Rules
    // rules: array of rules from database
    // inputs: [uan => x1, raport => x2, kompetensi => x3]
    public static function inferensi($inputs, $rules) {
        $mu_uan = self::uan($inputs['uan']);
        $mu_raport = self::raport($inputs['raport']);
        $mu_kompetensi = self::kompetensi($inputs['kompetensi']);

        $alpha_lulus = [];
        $alpha_tidak_lulus = [];

        foreach ($rules as $rule) {
            // Get values for this rule's condition
            $val_uan = $mu_uan[$rule['uan']] ?? 0.0;
            $val_raport = $mu_raport[$rule['raport']] ?? 0.0;
            $val_kompetensi = $mu_kompetensi[$rule['kompetensi']] ?? 0.0;

            // Operasi AND (MIN)
            $alpha = min($val_uan, $val_raport, $val_kompetensi);

            // Group by output category
            if ($rule['hasil'] === 'Lulus') {
                $alpha_lulus[] = $alpha;
            } else {
                $alpha_tidak_lulus[] = $alpha;
            }
        }

        // Operasi Agregasi (MAX)
        $max_lulus = !empty($alpha_lulus) ? max($alpha_lulus) : 0.0;
        $max_tidak_lulus = !empty($alpha_tidak_lulus) ? max($alpha_tidak_lulus) : 0.0;

        return [
            'Lulus' => $max_lulus,
            'Tidak Lulus' => $max_tidak_lulus
        ];
    }

    // 3. Defuzzifikasi: Centroid (COG)
    // alpha_outputs: ['Lulus' => max_lulus, 'Tidak Lulus' => max_tidak_lulus]
    public static function defuzzifikasi($alpha_outputs) {
        $a_lulus = $alpha_outputs['Lulus'];
        $a_tidak_lulus = $alpha_outputs['Tidak Lulus'];

        $num = 0.0;
        $den = 0.0;
        
        // Discretization of output variable z from 0 to 100 with step size 1
        for ($z = 0; $z <= 100; $z++) {
            // Degree of membership in output sets at point z
            $mu_z_tidak_lulus = self::mu_tidak_lulus($z);
            $mu_z_lulus = self::mu_lulus($z);

            // Apply aggregation MIN with the alpha cutoff for each set
            $val_tl = min($a_tidak_lulus, $mu_z_tidak_lulus);
            $val_l = min($a_lulus, $mu_z_lulus);

            // Aggregate using MAX
            $mu_agregasi = max($val_tl, $val_l);

            // Centroid sums
            $num += $z * $mu_agregasi;
            $den += $mu_agregasi;
        }

        // COG final value
        if ($den == 0) {
            return 50.0; // Neutral fallback if no rules fired with alpha > 0
        }

        return round($num / $den, 4);
    }
}
?>
