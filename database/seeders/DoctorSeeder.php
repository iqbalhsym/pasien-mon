<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Doctor;
use ZipArchive;
use SimpleXMLElement;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $file = database_path('seeders/DATA DOKTER.xlsx');
        if (!file_exists($file)) {
            $this->command->error("Excel file not found at: $file");
            return;
        }

        $zip = new ZipArchive();
        if ($zip->open($file) !== TRUE) {
            $this->command->error("Failed to open Excel zip archive: $file");
            return;
        }

        // 1. Read shared strings XML
        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml) {
            $xml = simplexml_load_string($sharedStringsXml);
            foreach ($xml->si as $si) {
                if (isset($si->t)) {
                    $sharedStrings[] = (string)$si->t;
                } else {
                    $text = '';
                    foreach ($si->r as $r) {
                        $text .= (string)$r->t;
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        // 2. Read sheet1 XML
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (!$sheetXml) {
            $this->command->error("Failed to read sheet1.xml inside Excel file.");
            $zip->close();
            return;
        }

        $xml = simplexml_load_string($sheetXml);
        $insertedCount = 0;
        $rowCount = 0;

        foreach ($xml->sheetData->row as $row) {
            $rowCount++;
            if ($rowCount === 1) {
                // Skip header row
                continue;
            }

            $rowData = [];
            foreach ($row->c as $c) {
                $ref = (string)$c['r']; // e.g. A2, B2
                $col = preg_replace('/[0-9]/', '', $ref);
                $type = (string)$c['t'];
                $val = (string)$c->v;
                
                if ($type === 's' && isset($sharedStrings[(int)$val])) {
                    $rowData[$col] = $sharedStrings[(int)$val];
                } else {
                    $rowData[$col] = $val;
                }
            }

            $name = $rowData['B'] ?? null;
            $ksm = $rowData['D'] ?? null;

            if (!empty($name)) {
                Doctor::updateOrCreate(
                    ['name' => trim($name)],
                    ['ksm' => $ksm ? trim($ksm) : null]
                );
                $insertedCount++;
            }
        }

        $zip->close();
        $this->command->info("Doctor synchronization complete! Seeded $insertedCount doctors.");
    }
}
