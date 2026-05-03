<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class StudentsBulkUploadController extends Controller
{
    public function create(): View
    {
        return view('students.bulk_upload');
    }

    public function downloadTemplate()
    {
        $genders = ['Male', 'Female', 'Other'];
        $religions = ['Buddhism', 'Hinduism', 'Islam', 'Christianity', 'Other'];
        $hearAbout = ['Facebook', 'Friends', 'TV', 'Ads', 'Other'];
        $yesNo = ['Yes', 'No'];

        $classRooms = ClassRoom::query()
            ->orderByRaw('level is null')
            ->orderBy('level')
            ->orderBy('name')
            ->get(['id', 'name', 'level', 'monthly_fee']);

        $headers = [
            'admission_number',
            'name*',
            'first_name*',
            'other_names',
            'name_with_initial*',
            'gender*',
            'date_of_birth*',
            'religion*',
            'nationality',
            'phone',
            'address',
            'parent_address*',
            'use_guardian (Yes/No)',
            'guardian_name',
            'guardian_relationship',
            'guardian_phone',
            'joining_date',
            'fee_start_date',
            'hear_about_us',
            'desired_class',
            'medical_history',
            'long_term_medication (Yes/No)*',
            'learning_disabilities (Yes/No)*',
            'previous_school',
            'previous_grade',
            'siblings',
            'has_siblings_in_college (Yes/No)*',
            'father_name_with_initial',
            'father_nic_passport',
            'father_religion',
            'father_nationality',
            'father_occupation',
            'father_phone',
            'father_whatsapp',
            'father_office_phone',
            'father_emergency_number',
            'mother_name_with_initial',
            'mother_nic_passport',
            'mother_religion',
            'mother_nationality',
            'mother_occupation',
            'mother_phone',
            'mother_whatsapp',
            'mother_office_phone',
            'mother_emergency_number',
            'class_room_name*',
            'monthly_fee (auto)',
            'active (auto)',
        ];

        $sheetName = 'Students';
        $listsName = 'Lists';
        $spreadsheet = new Spreadsheet();

        $studentsSheet = $spreadsheet->getActiveSheet();
        $studentsSheet->setTitle($sheetName);

        $listsSheet = $spreadsheet->createSheet();
        $listsSheet->setTitle($listsName);
        $listsSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

        // Write lists
        $listsSheet->setCellValue('A1', 'Genders');
        foreach ($genders as $i => $v) {
            $listsSheet->setCellValue('A' . (2 + $i), $v);
        }
        $listsSheet->setCellValue('B1', 'Religions');
        foreach ($religions as $i => $v) {
            $listsSheet->setCellValue('B' . (2 + $i), $v);
        }
        $listsSheet->setCellValue('C1', 'HearAbout');
        foreach ($hearAbout as $i => $v) {
            $listsSheet->setCellValue('C' . (2 + $i), $v);
        }
        $listsSheet->setCellValue('D1', 'YesNo');
        foreach ($yesNo as $i => $v) {
            $listsSheet->setCellValue('D' . (2 + $i), $v);
        }

        // Class rooms: Name + MonthlyFee
        $listsSheet->setCellValue('F1', 'ClassRoomName');
        $listsSheet->setCellValue('G1', 'MonthlyFee');
        foreach ($classRooms as $i => $cr) {
            $row = 2 + $i;
            $listsSheet->setCellValue('F' . $row, $cr->name);
            $listsSheet->setCellValue('G' . $row, (float) ($cr->monthly_fee ?? 0));
        }

        // Named ranges (simple absolute references)
        $genderRange = $listsName . '!$A$2:$A$' . (count($genders) + 1);
        $religionRange = $listsName . '!$B$2:$B$' . (count($religions) + 1);
        $hearRange = $listsName . '!$C$2:$C$' . (count($hearAbout) + 1);
        $yesNoRange = $listsName . '!$D$2:$D$' . (count($yesNo) + 1);
        $classRange = $listsName . '!$F$2:$F$' . (max(1, $classRooms->count()) + 1);
        $classTableRange = $listsName . '!$F$2:$G$' . (max(1, $classRooms->count()) + 1);

        // Header row
        foreach ($headers as $idx => $header) {
            $col = Coordinate::stringFromColumnIndex($idx + 1);
            $studentsSheet->setCellValue($col . '1', $header);
        }
        $studentsSheet->getStyle('A1:' . Coordinate::stringFromColumnIndex(count($headers)) . '1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '111827']],
        ]);
        $studentsSheet->freezePane('A2');

        // Column widths
        foreach (range(1, count($headers)) as $i) {
            $col = Coordinate::stringFromColumnIndex($i);
            $studentsSheet->getColumnDimension($col)->setWidth(20);
        }

        // Identify important columns
        $colIndex = array_flip($headers);
        $colGender = $colIndex['gender*'] + 1;
        $colReligion = $colIndex['religion*'] + 1;
        $colHear = $colIndex['hear_about_us'] + 1;
        $colUseGuardian = $colIndex['use_guardian (Yes/No)'] + 1;
        $colLtm = $colIndex['long_term_medication (Yes/No)*'] + 1;
        $colLd = $colIndex['learning_disabilities (Yes/No)*'] + 1;
        $colSibCollege = $colIndex['has_siblings_in_college (Yes/No)*'] + 1;
        $colFatherReligion = $colIndex['father_religion'] + 1;
        $colMotherReligion = $colIndex['mother_religion'] + 1;
        $colClass = $colIndex['class_room_name*'] + 1;
        $colMonthlyFee = $colIndex['monthly_fee (auto)'] + 1;
        $colActive = $colIndex['active (auto)'] + 1;
        $colNationality = $colIndex['nationality'] + 1;
        $colJoining = $colIndex['joining_date'] + 1;
        $colFeeStart = $colIndex['fee_start_date'] + 1;
        $colDob = $colIndex['date_of_birth*'] + 1;

        $maxRows = 51; // 50 students + sample row (row 2)
        $dataStartRow = 2;

        // Sample row (row 2)
        $sample = [
            'admission_number' => 'STU-2026-001',
            'name*' => 'Saman Perera',
            'first_name*' => 'Saman',
            'other_names' => 'Kumara',
            'name_with_initial*' => 'S. K. Perera',
            'gender*' => 'Male',
            'date_of_birth*' => '2018-01-15',
            'religion*' => 'Buddhism',
            'nationality' => 'Sri Lankan',
            'phone' => '0771234567',
            'address' => 'No 123, Temple Road, Colombo',
            'parent_address*' => 'No 123, Temple Road, Colombo',
            'use_guardian (Yes/No)' => 'No',
            'guardian_name' => '',
            'guardian_relationship' => '',
            'guardian_phone' => '',
            'joining_date' => date('Y-m-d'),
            'fee_start_date' => date('Y-m-01'),
            'hear_about_us' => 'Facebook',
            'desired_class' => '',
            'medical_history' => '',
            'long_term_medication (Yes/No)*' => 'No',
            'learning_disabilities (Yes/No)*' => 'No',
            'previous_school' => 'ABC Preschool',
            'previous_grade' => 'KG',
            'siblings' => '',
            'has_siblings_in_college (Yes/No)*' => 'No',
            'father_name_with_initial' => 'N. Perera',
            'father_nic_passport' => '',
            'father_religion' => 'Buddhism',
            'father_nationality' => 'Sri Lankan',
            'father_occupation' => '',
            'father_phone' => '0711234567',
            'father_whatsapp' => '',
            'father_office_phone' => '',
            'father_emergency_number' => '',
            'mother_name_with_initial' => '',
            'mother_nic_passport' => '',
            'mother_religion' => '',
            'mother_nationality' => '',
            'mother_occupation' => '',
            'mother_phone' => '',
            'mother_whatsapp' => '',
            'mother_office_phone' => '',
            'mother_emergency_number' => '',
            'class_room_name*' => $classRooms->first()?->name ?? '',
            'monthly_fee (auto)' => '',
            'active (auto)' => '1',
        ];
        foreach ($headers as $idx => $header) {
            $key = $header;
            $value = $sample[$key] ?? '';
            $col = Coordinate::stringFromColumnIndex($idx + 1);
            $studentsSheet->setCellValueExplicit($col . '2', $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }

        // Set formats for date columns
        foreach ([$colDob, $colJoining, $colFeeStart] as $dateCol) {
            $col = Coordinate::stringFromColumnIndex($dateCol);
            $studentsSheet->getStyle($col . $dataStartRow . ':' . $col . $maxRows)
                ->getNumberFormat()->setFormatCode('yyyy-mm-dd');
        }
        // Currency format for fee
        $feeCol = Coordinate::stringFromColumnIndex($colMonthlyFee);
        $studentsSheet->getStyle($feeCol . $dataStartRow . ':' . $feeCol . $maxRows)
            ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

        // Default nationality + active, and formula for monthly fee
        $classColLetter = Coordinate::stringFromColumnIndex($colClass);
        for ($r = $dataStartRow; $r <= $maxRows; $r++) {
            // Nationality default
            $natCol = Coordinate::stringFromColumnIndex($colNationality);
            if ($r !== 2) {
                $studentsSheet->setCellValueExplicit($natCol . $r, 'Sri Lankan', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }

            // Active always 1
            $actCol = Coordinate::stringFromColumnIndex($colActive);
            $studentsSheet->setCellValueExplicit($actCol . $r, '1', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

            // Monthly fee formula (lookup by class name)
            $feeCell = Coordinate::stringFromColumnIndex($colMonthlyFee) . $r;
            $classCell = $classColLetter . $r;
            $studentsSheet->setCellValue($feeCell, '=IFERROR(VLOOKUP(' . $classCell . ',' . $classTableRange . ',2,FALSE),0)');
        }

        // Data validations (dropdowns)
        $this->applyDropdown($studentsSheet, $colGender, $dataStartRow, $maxRows, '=' . $genderRange);
        $this->applyDropdown($studentsSheet, $colReligion, $dataStartRow, $maxRows, '=' . $religionRange);
        $this->applyDropdown($studentsSheet, $colFatherReligion, $dataStartRow, $maxRows, '=' . $religionRange);
        $this->applyDropdown($studentsSheet, $colMotherReligion, $dataStartRow, $maxRows, '=' . $religionRange);
        $this->applyDropdown($studentsSheet, $colHear, $dataStartRow, $maxRows, '=' . $hearRange);
        $this->applyDropdown($studentsSheet, $colUseGuardian, $dataStartRow, $maxRows, '=' . $yesNoRange);
        $this->applyDropdown($studentsSheet, $colLtm, $dataStartRow, $maxRows, '=' . $yesNoRange);
        $this->applyDropdown($studentsSheet, $colLd, $dataStartRow, $maxRows, '=' . $yesNoRange);
        $this->applyDropdown($studentsSheet, $colSibCollege, $dataStartRow, $maxRows, '=' . $yesNoRange);
        $this->applyDropdown($studentsSheet, $colClass, $dataStartRow, $maxRows, '=' . $classRange);

        // Slight highlight for required columns
        foreach ($headers as $idx => $header) {
            if (str_contains($header, '*')) {
                $col = Coordinate::stringFromColumnIndex($idx + 1);
                $studentsSheet->getStyle($col . '1')->getFont()->setBold(true);
                $studentsSheet->getStyle($col . '1')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFF59D'));
            }
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'student_bulk_template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function applyDropdown($sheet, int $colIndex, int $startRow, int $endRow, string $formula): void
    {
        $col = Coordinate::stringFromColumnIndex($colIndex);
        for ($row = $startRow; $row <= $endRow; $row++) {
            $cell = $col . $row;
            $validation = $sheet->getCell($cell)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1($formula);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt,xlsx,xls'],
        ]);

        $path = $validated['csv']->store('tmp');
        $full = Storage::path($path);

        try {
            $created = 0;
            $skipped = 0;
            $errors = [];

            $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
            if (in_array($ext, ['xlsx', 'xls'], true)) {
                [$created, $skipped, $errors] = $this->importXlsx($full);
            } else {
                [$created, $skipped, $errors] = $this->importCsv($full);
            }

            $msg = "Imported {$created} students.";
            if ($skipped > 0) {
                $msg .= " Skipped {$skipped} rows.";
            }
            if (!empty($errors)) {
                $msg .= ' First errors: ' . implode(' | ', array_slice($errors, 0, 3));
            }

            $type = ($created > 0 && $skipped === 0 && empty($errors)) ? 'success' : 'warning';

            return redirect()->route('students.index')
                ->with('status', $msg)
                ->with('status_type', $type);
        } catch (\Throwable $e) {
            Log::error('Student bulk upload failed.', [
                'user_id' => $request->user()?->id,
                'file' => $validated['csv']->getClientOriginalName(),
                'route' => $request->route()?->getName(),
                'action' => optional($request->route())->getActionName(),
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'csv' => 'Import failed. Please validate the file format and try again.',
            ]);
        } finally {
            Storage::delete($path);
        }
    }

    private function normalizeText(?string $value): string
    {
        $value = (string) ($value ?? '');
        // Convert non-breaking spaces to regular spaces, then trim + collapse whitespace
        $value = str_replace("\xC2\xA0", ' ', $value);
        $value = trim($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return $value;
    }

    private function resolveClassRoom(?string $classRoomName, $classRoomLevel = null): ?ClassRoom
    {
        static $byName = null;
        static $byNameLower = null;

        if ($byName === null || $byNameLower === null) {
            $all = ClassRoom::query()->get(['id', 'name', 'level', 'monthly_fee']);
            $byName = [];
            $byNameLower = [];
            foreach ($all as $cr) {
                $n = $this->normalizeText($cr->name);
                $byName[$n] = $cr;
                $byNameLower[mb_strtolower($n)] = $cr;
            }
        }

        $name = $this->normalizeText($classRoomName);
        if ($name !== '') {
            if (isset($byName[$name])) return $byName[$name];
            $lower = mb_strtolower($name);
            if (isset($byNameLower[$lower])) return $byNameLower[$lower];
        }

        if ($classRoomLevel !== null && $classRoomLevel !== '') {
            $level = (int) $classRoomLevel;
            return ClassRoom::query()->where('level', $level)->first();
        }

        return null;
    }

    private function importCsv(string $fullPath): array
    {
        $handle = fopen($fullPath, 'r');
        $header = null;
        $created = 0;
        $skipped = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            if ($header === null) {
                $header = array_map(function ($h) {
                    $h = trim((string) $h);
                    $h = trim(str_replace('*', '', $h));
                    $h = preg_replace('/\s*\(.*\)\s*$/', '', $h); // remove "(Yes/No)" hints
                    return trim($h);
                }, $row);
                continue;
            }

            $data = [];
            foreach ($header as $idx => $key) {
                if ($key === '') continue;
                $data[$key] = isset($row[$idx]) ? trim((string) $row[$idx]) : null;
            }

            [$ok, $err] = $this->importOneRow($data);
            if ($ok) {
                $created++;
            } else {
                $skipped++;
                if ($err) $errors[] = $err;
            }
        }
        fclose($handle);

        return [$created, $skipped, $errors];
    }

    private function importXlsx(string $fullPath): array
    {
        $spreadsheet = IOFactory::load($fullPath);
        $sheet = $spreadsheet->getSheet(0);

        $highestRow = $sheet->getHighestRow();
        $highestCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        $header = [];
        for ($c = 1; $c <= $highestCol; $c++) {
            $cellRef = Coordinate::stringFromColumnIndex($c) . '1';
            $v = (string) $sheet->getCell($cellRef)->getValue();
            $v = trim(str_replace('*', '', $v));
            $v = preg_replace('/\s*\(.*\)\s*$/', '', $v); // remove "(Yes/No)" hints
            $header[$c] = trim($v);
        }

        $created = 0;
        $skipped = 0;
        $errors = [];

        for ($r = 2; $r <= $highestRow; $r++) {
            $data = [];
            $isEmpty = true;
            for ($c = 1; $c <= $highestCol; $c++) {
                $key = $header[$c] ?? '';
                if ($key === '') continue;

                $cellRef = Coordinate::stringFromColumnIndex($c) . $r;
                $cell = $sheet->getCell($cellRef);
                $val = $cell->getCalculatedValue();
                if ($val !== null && $val !== '') {
                    $isEmpty = false;
                }

                // Normalize excel dates for known columns
                if (in_array($key, ['date_of_birth', 'joining_date', 'fee_start_date'], true)) {
                    $data[$key] = $this->normalizeDateValue($val);
                } else {
                    $data[$key] = is_string($val) ? trim($val) : $val;
                }
            }

            if ($isEmpty) {
                continue;
            }

            [$ok, $err] = $this->importOneRow($data);
            if ($ok) {
                $created++;
            } else {
                $skipped++;
                if ($err) $errors[] = "Row {$r}: {$err}";
            }
        }

        return [$created, $skipped, $errors];
    }

    private function normalizeDateValue($value): ?string
    {
        if ($value === null || $value === '') return null;

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        $value = trim((string) $value);
        if ($value === '') return null;

        // Accept YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return $value;

        // Accept DD-MM-YYYY
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $value, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }

        // Fallback parse
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function toBool01($value): ?bool
    {
        if ($value === null) return null;
        if (is_bool($value)) return $value;
        $v = strtolower(trim((string) $value));
        if ($v === '') return null;
        if (in_array($v, ['1', 'yes', 'y', 'true'], true)) return true;
        if (in_array($v, ['0', 'no', 'n', 'false'], true)) return false;
        return null;
    }

    private function importOneRow(array $data): array
    {
        $admissionNumber = isset($data['admission_number']) ? trim((string) $data['admission_number']) : null;
        $name = isset($data['name']) ? trim((string) $data['name']) : '';

        if ($name === '' && ($admissionNumber === null || $admissionNumber === '')) {
            return [false, 'Missing admission_number and name'];
        }

        $classRoomName = $this->normalizeText(isset($data['class_room_name']) ? (string) $data['class_room_name'] : null);
        $classRoomLevel = $data['class_room_level'] ?? null;
        $classRoom = $this->resolveClassRoom($classRoomName, $classRoomLevel);
        if (!$classRoom) {
            $shown = $classRoomName !== '' ? $classRoomName : (string) ($classRoomLevel ?? '');
            return [false, "Invalid class_room_name: {$shown}"];
        }

        // Defaults
        $nationality = !empty($data['nationality']) ? trim((string) $data['nationality']) : 'Sri Lankan';
        $joiningDate = $this->normalizeDateValue($data['joining_date'] ?? null) ?? date('Y-m-d');
        $feeStartDate = $this->normalizeDateValue($data['fee_start_date'] ?? null);
        $dob = $this->normalizeDateValue($data['date_of_birth'] ?? null);

        $useGuardian = $this->toBool01($data['use_guardian'] ?? null) ?? false;
        $longTermMedication = $this->toBool01($data['long_term_medication'] ?? null);
        $learningDisabilities = $this->toBool01($data['learning_disabilities'] ?? null);
        $hasSiblingsInCollege = $this->toBool01($data['has_siblings_in_college'] ?? null);

        // Monthly fee: prefer provided, otherwise class fee
        $monthlyFee = $classRoom->monthly_fee ?? 0;
        if (isset($data['monthly_fee']) && $data['monthly_fee'] !== '' && $data['monthly_fee'] !== null) {
            $monthlyFee = (float) $data['monthly_fee'];
        }

        // Compute due amount like StudentController does
        $dueAmount = (float) $monthlyFee;
        if (!empty($feeStartDate)) {
            $start = \Carbon\Carbon::parse($feeStartDate)->startOfDay();
            $now = now();
            $months = $now->lt($start) ? 0 : ($start->diffInMonths($now) + 1);
            $dueAmount = (float) $monthlyFee * max(0, $months);
        }

        // Academic year from joining date
        $year = \Carbon\Carbon::parse($joiningDate)->year;
        $academicYear = $year . '-' . ($year + 1);

        // Some required fields in UI
        $firstName = isset($data['first_name']) ? trim((string) $data['first_name']) : null;
        $nameWithInitial = isset($data['name_with_initial']) ? trim((string) $data['name_with_initial']) : null;
        $parentAddress = isset($data['parent_address']) ? trim((string) $data['parent_address']) : null;
        $gender = isset($data['gender']) ? trim((string) $data['gender']) : null;
        $religion = isset($data['religion']) ? trim((string) $data['religion']) : null;

        if ($name === '' || !$firstName || !$nameWithInitial || !$parentAddress || !$gender || !$religion || !$dob) {
            return [false, 'Missing required fields (*)'];
        }
        if ($longTermMedication === null || $learningDisabilities === null || $hasSiblingsInCollege === null) {
            return [false, 'Missing required Yes/No fields'];
        }

        $attributes = [
            'name' => $name,
            'first_name' => $firstName,
            'other_names' => $data['other_names'] ?? null,
            'name_with_initial' => $nameWithInitial,
            'address' => $data['address'] ?? null,
            'parent_address' => $parentAddress,
            'phone' => $data['phone'] ?? null,
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
            'gender' => $gender,
            'date_of_birth' => $dob,
            'use_guardian' => $useGuardian,
            'guardian_name' => $data['guardian_name'] ?? null,
            'guardian_relationship' => $data['guardian_relationship'] ?? null,
            'guardian_phone' => $data['guardian_phone'] ?? null,
            'joining_date' => $joiningDate,
            'fee_start_date' => $feeStartDate,
            'year' => $academicYear,
            'class_room_id' => $classRoom->id,
            'class' => $classRoom->name,
            'religion' => $religion,
            'nationality' => $nationality,
            'desired_class' => $data['desired_class'] ?? null,
            'medical_history' => $data['medical_history'] ?? null,
            'long_term_medication' => (bool) $longTermMedication,
            'learning_disabilities' => (bool) $learningDisabilities,
            'previous_school' => $data['previous_school'] ?? null,
            'previous_grade' => $data['previous_grade'] ?? null,
            'siblings' => $data['siblings'] ?? null,
            'has_siblings_in_college' => (bool) $hasSiblingsInCollege,
            'father_name_with_initial' => $data['father_name_with_initial'] ?? null,
            'father_nic_passport' => $data['father_nic_passport'] ?? null,
            'father_religion' => $data['father_religion'] ?? null,
            'father_nationality' => $data['father_nationality'] ?? null,
            'father_occupation' => $data['father_occupation'] ?? null,
            'father_phone' => $data['father_phone'] ?? null,
            'father_whatsapp' => $data['father_whatsapp'] ?? null,
            'father_office_phone' => $data['father_office_phone'] ?? null,
            'father_emergency_number' => $data['father_emergency_number'] ?? null,
            'mother_name_with_initial' => $data['mother_name_with_initial'] ?? null,
            'mother_nic_passport' => $data['mother_nic_passport'] ?? null,
            'mother_religion' => $data['mother_religion'] ?? null,
            'mother_nationality' => $data['mother_nationality'] ?? null,
            'mother_occupation' => $data['mother_occupation'] ?? null,
            'mother_phone' => $data['mother_phone'] ?? null,
            'mother_whatsapp' => $data['mother_whatsapp'] ?? null,
            'mother_office_phone' => $data['mother_office_phone'] ?? null,
            'mother_emergency_number' => $data['mother_emergency_number'] ?? null,
            'hear_about_us' => $data['hear_about_us'] ?? null,
            'monthly_fee' => (float) $monthlyFee,
            'due_amount' => (float) $dueAmount,
            'active' => true,
        ];

        if ($admissionNumber !== null && $admissionNumber !== '') {
            Student::query()->updateOrCreate(
                ['admission_number' => $admissionNumber],
                $attributes
            );
        } else {
            Student::query()->updateOrCreate(
                [
                    'admission_number' => null,
                    'name' => $name,
                    'date_of_birth' => $dob,
                    'class_room_id' => $classRoom->id,
                ],
                $attributes
            );
        }

        return [true, null];
    }
}
