<?php

namespace App\Services\TeacherEvaluations;

use App\Models\Teacher;
use App\Models\TeacherEvaluation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class EvaluationExport
{
    public function download(Collection $evaluations, ?string $type, string $format, bool $blank = false, array $filters = [], ?Teacher $teacher = null)
    {
        if ($blank) {
            $evaluations = collect([new TeacherEvaluation(['type' => $type, 'criteria' => EvaluationForm::criteria(), 'status' => 'draft', 'teacher_id' => $teacher?->id, 'teacher_name' => $teacher?->name, 'subject_class' => $teacher?->assigned_classes])]);
        }
        $name = 'teacher-evaluation'.($blank ? '-'.$type.'-template' : ($evaluations->count() === 1 ? '-'.$evaluations->first()->id : '-reports')).'.'.$format;
        if ($format === 'pdf') {
            $pdf = Pdf::loadView('teacher-evaluations.pdf', compact('evaluations', 'blank', 'filters'))->setPaper('a4', 'landscape')->setOption('isRemoteEnabled', false);
            $pdf->render();
            $pdf->getDomPDF()->getCanvas()->page_text(714, 571, 'Page {PAGE_NUM} of {PAGE_COUNT}', null, 8, [0.4, 0.4, 0.4]);

            return $pdf->download($name)->header('Cache-Control', 'private, no-store');
        }
        $book = $this->workbook($evaluations, $blank, $filters);

        return response()->streamDownload(function () use ($book) {
            try {
                (new Xlsx($book))->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, $name, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Cache-Control' => 'private, no-store']);
    }

    public function workbook(Collection $evaluations, bool $blank = false, array $filters = []): Spreadsheet
    {
        $book = new Spreadsheet;
        $book->removeSheetByIndex(0);
        $book->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        $book->getDefaultStyle()->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        if (! $blank && $evaluations->count() !== 1) {
            $sheet = $this->sheet($book, 'Report list');
            $this->merged($sheet, 1, 'Teacher evaluation reports', true);
            $this->merged($sheet, 3, 'Filters: '.(collect($filters)->filter(fn ($v) => $v !== null && $v !== '')->map(fn ($v, $k) => str_replace('_', ' ', $k).': '.$v)->implode(' | ') ?: 'All records'));
            $this->header($sheet, 5, ['Record', 'Teacher', 'Type / section', 'Year / event', 'Event date', 'Review date', 'Status', 'Score / 100']);
            $row = 6;
            foreach ($evaluations as $evaluation) {
                $values = [$evaluation->id, $evaluation->teacher_name, $evaluation->type_label.' / '.$evaluation->section, $evaluation->type === 'annual' ? $evaluation->appraisal_year : $evaluation->event_name, $evaluation->event_date?->format('Y-m-d'), $evaluation->review_date?->format('Y-m-d'), ucfirst($evaluation->status), $evaluation->score];
                foreach ($values as $i => $value) {
                    if (in_array($i, [4, 5]) && $value) {
                        $value = Date::dateTimeToExcel(new \DateTimeImmutable($value));
                        $sheet->getStyle(chr(65 + $i).$row)->getNumberFormat()->setFormatCode('dd mmm yyyy');
                    }
                    $this->cell($sheet, chr(65 + $i).$row, $value, in_array($i, [0, 4, 5, 7]));
                }
                $sheet->getRowDimension($row++)->setRowHeight(48);
            }
            if (! $evaluations->count()) {
                $this->merged($sheet, 6, 'No evaluations match these filters.');
            }
            $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(5, 5);
            $sheet->freezePane('B6');
            $sheet->setAutoFilter('A5:H'.max(5, $row - 1));
        }
        foreach ($evaluations as $evaluation) {
            $suffix = $blank ? '' : ' '.$evaluation->id;
            if ($evaluation->type === 'annual') {
                $this->annual($this->sheet($book, 'Annual'.$suffix), $evaluation, $blank);
            }
            $this->feedback($this->sheet($book, ($evaluation->type === 'event' ? 'Event review' : 'Feedback').$suffix), $evaluation, $blank);
        }
        $instructions = $this->sheet($book, 'Instructions');
        $this->merged($instructions, 1, 'How to use teacher evaluation forms', true);
        $row = 3;
        foreach (EvaluationForm::instructions() as $i => $instruction) {
            $this->textBlock($instructions, $row, ($i + 1).'. '.$instruction, 45);
        }
        $book->setActiveSheetIndex(0);
        foreach ($book->getAllSheets() as $sheet) {
            $endColumn = str_starts_with($sheet->getTitle(), 'Annual') ? 'L' : 'H';
            $sheet->getPageSetup()->setPrintArea('A1:'.$endColumn.$sheet->getHighestRow());
            $sheet->getTabColor()->setRGB('7566A8');
            $sheet->getStyle('A1:'.$endColumn.'1')->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '29166F']]]);
            $sheet->getRowDimension(1)->setRowHeight(32);
            if (str_starts_with($sheet->getTitle(), 'Annual') || str_starts_with($sheet->getTitle(), 'Event review') || str_starts_with($sheet->getTitle(), 'Feedback')) {
                $sheet->getStyle('A2:'.$endColumn.'2')->applyFromArray(['font' => ['color' => ['rgb' => '29166F']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1EDF9']]]);
                $sheet->getStyle('A4:'.$endColumn.'7')->getFont()->getColor()->setRGB('493582');
            }
        }

        return $book;
    }

    private function sheet(Spreadsheet $book, string $name): Worksheet
    {
        $sheet = new Worksheet($book, $name);
        $book->addSheet($sheet);
        $sheet->setShowGridlines(false);
        foreach ([6, 29, 27, 27, 11, 12, 13, 35] as $i => $width) {
            $sheet->getColumnDimension(chr(65 + $i))->setWidth($width);
        }
        $sheet->getDefaultRowDimension()->setRowHeight(24);
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.3)->setBottom(0.35)->setLeft(0.3)->setRight(0.3);
        $sheet->getHeaderFooter()->setOddFooter('&LConfidential staff record&RPage &P of &N');

        return $sheet;
    }

    private function cell(Worksheet $sheet, string $address, mixed $value, bool $numeric = false): void
    {
        if ($value === null || $value === '') {
            return;
        }
        $sheet->setCellValueExplicit($address, $value, $numeric && is_numeric($value) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING);
    }

    private function merged(Worksheet $sheet, int $row, string $text, bool $heading = false, string $from = 'A', string $to = 'H'): void
    {
        $sheet->mergeCells("$from$row:$to$row");
        $this->cell($sheet, "$from$row", $text);
        if ($heading) {
            $sheet->getStyle("$from$row:$to$row")->applyFromArray([
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7566A8']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(28);
        }
    }

    private function header(Worksheet $sheet, int $row, array $values, string $to = 'H'): void
    {
        foreach ($values as $i => $value) {
            $this->cell($sheet, chr(65 + $i).$row, $value);
        }
        $sheet->getStyle("A$row:$to$row")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '29166F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(34);
    }

    private function profile(Worksheet $sheet, TeacherEvaluation $evaluation, bool $blank, string $title): void
    {
        $sheet->getHeaderFooter()->setOddHeader('&L'.str_replace('&', '&&', $evaluation->teacher_name ?: 'Teacher: ____________________').'&R'.($evaluation->type === 'annual' ? ($evaluation->appraisal_year ?: 'Appraisal year: ______') : 'Event review'));
        $this->merged($sheet, 1, (string) app('settings')->get('school.name', config('app.name')), true);
        $this->merged($sheet, 2, $title.($blank ? ' - Blank form' : ' - '.ucfirst($evaluation->status)), true);
        $this->merged($sheet, 4, 'Teacher: '.($evaluation->teacher_name ?: '_________________________________'), false, 'A', 'D');
        $this->merged($sheet, 4, 'Staff ID: '.($evaluation->staff_id ?: '___________________'), false, 'E', 'H');
        $this->merged($sheet, 5, 'Section: '.($evaluation->section ?: 'Preschool / Primary / Secondary / Other'), false, 'A', 'D');
        $this->merged($sheet, 5, 'Subject / class: '.($evaluation->subject_class ?: '___________________'), false, 'E', 'H');
        $this->merged($sheet, 6, 'Evaluator: '.($evaluation->evaluator ?: '_____________________________'), false, 'A', 'D');
        $this->merged($sheet, 6, 'Review date: '.($evaluation->review_date?->format('d M Y') ?: '___________________'), false, 'E', 'H');
        if ($evaluation->type === 'annual') {
            $this->merged($sheet, 7, 'Appraisal year: '.($evaluation->appraisal_year ?: '________').'    Years of service: '.($evaluation->years_of_service ?: '________'), false, 'A', 'D');
            $this->merged($sheet, 7, 'Period: '.($evaluation->period_start?->format('d M Y') ?: '________').' to '.($evaluation->period_end?->format('d M Y') ?: '________'), false, 'E', 'H');
        } else {
            $this->merged($sheet, 7, 'Event: '.($evaluation->event_name ?: '________________________'), false, 'A', 'D');
            $this->merged($sheet, 7, 'Event date: '.($evaluation->event_date?->format('d M Y') ?: '___________________'), false, 'E', 'H');
        }
    }

    private function annual(Worksheet $sheet, TeacherEvaluation $evaluation, bool $blank): void
    {
        $this->profile($sheet, $evaluation, $blank, 'Annual Teacher Performance Appraisal');
        foreach ([5, 24, 22, 22, 8, 8, 10, 8, 10, 8, 10, 24] as $index => $width) {
            $sheet->getColumnDimension(chr(65 + $index))->setWidth($width);
        }
        // Extend the profile across the three supervisor columns for a readable printed form.
        foreach ([1, 2] as $headingRow) {
            $sheet->unmergeCells("A$headingRow:H$headingRow");
            $sheet->mergeCells("A$headingRow:L$headingRow");
        }
        foreach (range(4, 7) as $profileRow) {
            $value = $sheet->getCell('E'.$profileRow)->getValue();
            $sheet->unmergeCells("A$profileRow:D$profileRow");
            $sheet->unmergeCells("E$profileRow:H$profileRow");
            $sheet->setCellValue('E'.$profileRow, null);
            $sheet->mergeCells("A$profileRow:F$profileRow");
            $sheet->mergeCells("G$profileRow:L$profileRow");
            $this->cell($sheet, 'G'.$profileRow, $value);
        }
        foreach (['A' => 'D', 'E' => 'H', 'I' => 'L'] as $from => $to) {
            $index = (int) ((ord($from) - 65) / 4);
            $name = $evaluation->supervisors[$index] ?? ($index === 0 ? $evaluation->evaluator : null);
            $this->merged($sheet, 8, 'Supervisor '.($index + 1).': '.($name ?: '________________________'), false, $from, $to);
        }
        $sheet->getStyle('A8:L9')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1EDF9');
        $sheet->getRowDimension(8)->setRowHeight(32);
        $this->merged($sheet, 9, 'Rating: 5 Outstanding | 4 Exceeds expectations | 3 Meets expectations | 2 Needs improvement | 1 Unsatisfactory', false, 'A', 'L');
        $this->merged($sheet, 10, 'One supervisor is enough; the others are optional. Overall = average of completed supervisors. Finish or clear partial columns before completion.', false, 'A', 'L');
        $this->header($sheet, 11, ['No.', 'Performance area', 'Expected standard / evidence', '', 'Weight %', 'S1 Rating 1-5', 'S1 Weighted score', 'S2 Rating 1-5', 'S2 Weighted score', 'S3 Rating 1-5', 'S3 Weighted score', 'Remarks (see below if entered)'], 'L');
        $sheet->getRowDimension(11)->setRowHeight(46);
        $sheet->mergeCells('C11:D11');
        $row = 12;
        $remarks = [];
        foreach ($evaluation->criteria ?? EvaluationForm::criteria() as $criterion) {
            $this->styleTableRow($sheet, $row, 'L', $row % 2 === 1);
            $this->cell($sheet, 'A'.$row, $criterion['id'], true);
            $this->cell($sheet, 'B'.$row, $criterion['area']);
            $sheet->mergeCells("C$row:D$row");
            $this->cell($sheet, 'C'.$row, $criterion['standard']);
            $this->cell($sheet, 'E'.$row, $criterion['weight'], true);
            foreach (['F' => 'G', 'H' => 'I', 'J' => 'K'] as $ratingColumn => $scoreColumn) {
                $index = (int) ((ord($ratingColumn) - 70) / 2);
                $this->cell($sheet, $ratingColumn.$row, EvaluationForm::ratings($criterion)[$index], true);
                $sheet->setCellValue($scoreColumn.$row, '=IF('.$ratingColumn.$row.'="","",E'.$row.'*'.$ratingColumn.$row.'/5)');
                $sheet->getStyle($ratingColumn.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF6D9');
                $sheet->getStyle($scoreColumn.$row)->getNumberFormat()->setFormatCode('0.00');
                $sheet->getStyle($scoreColumn.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1EDF9');
                $validation = $sheet->getCell($ratingColumn.$row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_WHOLE)->setOperator(DataValidation::OPERATOR_BETWEEN)->setFormula1('1')->setFormula2('5')->setAllowBlank(true)->setShowErrorMessage(true)->setErrorStyle(DataValidation::STYLE_STOP)->setError('Enter a whole number from 1 to 5.');
            }
            $sheet->getStyle("E$row:K$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            if (! empty($criterion['remarks'])) {
                $this->cell($sheet, 'L'.$row, 'Criterion '.$criterion['id'].' remarks are below the table.');
                $remarks[] = $criterion;
            }
            $sheet->getStyle("A$row:L$row")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('DCE1E8');
            $sheet->getRowDimension($row++)->setRowHeight(68);
        }
        $this->merged($sheet, 25, 'TOTAL WEIGHT / OVERALL AVERAGE', true, 'A', 'D');
        $sheet->getStyle('A25:L25')->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '29166F']]]);
        $sheet->getStyle('A26:L26')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1EDF9');
        $sheet->setCellValue('E25', '=SUM(E12:E24)');
        $sheet->setCellValue('G25', '=IF(COUNT(G26,I26,K26)=0,"",ROUND(AVERAGE(G26,I26,K26),2))');
        $sheet->getStyle('G25')->getNumberFormat()->setFormatCode('0.00');
        $sheet->mergeCells('H25:L25');
        $sheet->setCellValue('H25', '=IF(G25="","Ratings incomplete",IF(G25>=90,"Outstanding",IF(G25>=80,"Exceeds expectations",IF(G25>=70,"Meets expectations well",IF(G25>=60,"Needs improvement","Unsatisfactory")))))');
        $this->merged($sheet, 26, 'COMPLETED SUPERVISOR TOTALS', false, 'A', 'E');
        foreach (['F' => 'G', 'H' => 'I', 'J' => 'K'] as $ratingColumn => $scoreColumn) {
            $sheet->setCellValue($scoreColumn.'26', '=IF(COUNT('.$ratingColumn.'12:'.$ratingColumn.'24)=13,ROUND(SUM('.$scoreColumn.'12:'.$scoreColumn.'24),2),"")');
            $sheet->getStyle($scoreColumn.'26')->getNumberFormat()->setFormatCode('0.00');
        }
        $row = 27;
        foreach ($remarks as $criterion) {
            $this->merged($sheet, $row++, 'Criterion '.$criterion['id'].' - '.$criterion['area'], true, 'A', 'L');
            $this->textBlock($sheet, $row, $criterion['remarks'], 28, 'A', 'L');
        }
        $this->merged($sheet, $row++, 'Performance bands and suggested management action', true, 'A', 'L');
        foreach (['90-100 Outstanding: Consider promotion or a higher increment, subject to approval and budget.', '80 to below 90 Exceeds expectations: Consider an increment and expanded responsibilities.', '70 to below 80 Meets expectations well: Consider an increment in line with policy and set growth goals.', '60 to below 70 Needs improvement: Document an improvement plan and review date.', 'Below 60 Unsatisfactory: Set a formal improvement plan and follow-up review.', 'Scores inform management review. They do not automatically change salary or promotion.'] as $line) {
            $this->textBlock($sheet, $row, $line, 28, 'A', 'L');
        }
        $sheet->freezePane('C12');
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(11, 11);
    }

    private function feedback(Worksheet $sheet, TeacherEvaluation $evaluation, bool $blank): void
    {
        foreach ([22, 18, 18, 23, 23, 23, 15, 22] as $index => $width) {
            $sheet->getColumnDimension(chr(65 + $index))->setWidth($width);
        }
        $this->profile($sheet, $evaluation, $blank, 'Evidence, Feedback and Development Plan');
        $row = 9;
        if ($evaluation->type === 'event') {
            $this->merged($sheet, $row++, 'Assigned responsibility / duties', true);
            $this->textBlock($sheet, $row, $evaluation->responsibility ?? '', $blank ? 42 : 28);
            $row++;
        }
        foreach (['evidence' => ['A. Evidence and examples', EvaluationForm::EVIDENCE_FIELDS], 'development' => ['B. Professional development action plan', EvaluationForm::DEVELOPMENT_FIELDS]] as $group => [$title, $fields]) {
            $this->merged($sheet, $row++, $title, true);
            $this->feedbackTable($sheet, $row, $blank ? array_fill(0, 4, []) : ($evaluation->$group ?? []), $fields, $group, $blank);
            $row++;
        }
        foreach (['teacher_comments' => "C. Teacher's comments", 'management_comments' => 'Management decision / comments'] as $field => $label) {
            $this->merged($sheet, $row++, $label, true);
            $this->textBlock($sheet, $row, $evaluation->$field ?? '', $blank ? 75 : 28);
        }
        foreach (['teacher' => 'Teacher', 'evaluator' => 'Evaluator'] as $field => $label) {
            $this->merged($sheet, $row++, $label.' signature: __________________________  Recorded name: '.($evaluation->{$field.'_signed_name'} ?: '____________________').'  Date: '.($evaluation->{$field.'_signed_date'}?->format('d M Y') ?: '____________________'));
        }
        $this->textBlock($sheet, $row, 'Recorded names and dates are transcribed from physical signatures, not digital signatures. A signature confirms discussion, not necessarily agreement.');
        $this->merged($sheet, $row++, 'Follow-up completed date: '.($evaluation->follow_up_completed_date?->format('d M Y') ?: '________________________'));
        $this->merged($sheet, $row++, 'Follow-up review notes', true);
        $this->textBlock($sheet, $row, $evaluation->follow_up_notes ?? '', $blank ? 55 : 28);
        $sheet->freezePane('B'.($evaluation->type === 'event' ? 14 : 11));
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 2);
    }

    private function styleTableRow(Worksheet $sheet, int $row, string $to, bool $stripe): void
    {
        $sheet->getStyle("A$row:$to$row")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $stripe ? 'F4F5F7' : 'FFFFFF']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DED8EB']]],
        ]);
    }

    private function feedbackTable(Worksheet $sheet, int &$row, array $entries, array $fields, string $group, bool $blank): void
    {
        $columns = $group === 'evidence' ? ['A:A', 'B:C', 'D:D', 'E:E', 'F:F', 'G:G', 'H:H'] : ['A:A', 'B:C', 'D:D', 'E:E', 'F:F', 'G:H'];
        $this->header($sheet, $row, []);
        $sheet->getRowDimension($row)->setRowHeight(36);
        foreach (array_values($fields) as $index => $label) {
            [$from, $to] = explode(':', $columns[$index]);
            if ($from !== $to) {
                $sheet->mergeCells("$from$row:$to$row");
            }
            $this->cell($sheet, $from.$row, $label);
        }
        $row++;
        $rows = EvaluationForm::tableRows($entries, $fields);
        if (! count($rows)) {
            $this->textBlock($sheet, $row, 'No entries recorded.');
        }
        foreach ($rows as $entry) {
            $this->styleTableRow($sheet, $row, 'H', $entry['entry'] % 2 === 0);
            $height = $blank ? 42 : 32;
            foreach (array_keys($fields) as $index => $key) {
                [$from, $to] = explode(':', $columns[$index]);
                if ($from !== $to) {
                    $sheet->mergeCells("$from$row:$to$row");
                }
                $value = $entry['cells'][$key];
                if ($index === 0 && $entry['continued']) {
                    $value = 'Entry '.$entry['entry']." continued\n".$value;
                }
                if (str_ends_with($key, '_date') && $value !== '') {
                    $this->cell($sheet, $from.$row, Date::dateTimeToExcel(new \DateTimeImmutable($value)), true);
                    $sheet->getStyle($from.$row)->getNumberFormat()->setFormatCode('dd mmm yyyy');
                } else {
                    $this->cell($sheet, $from.$row, $value);
                }
                $width = 0;
                foreach (range($from, $to) as $column) {
                    $width += $sheet->getColumnDimension($column)->getWidth();
                }
                $charactersPerLine = max(10, (int) ($width * .85));
                $lines = array_sum(array_map(fn ($line) => max(1, (int) ceil(mb_strlen($line) / $charactersPerLine)), preg_split('/\R/u', $value) ?: ['']));
                $height = max($height, $lines * 14 + 12);
            }
            $sheet->getRowDimension($row++)->setRowHeight(min(390, $height));
        }
    }

    private function textBlock(Worksheet $sheet, int &$row, string $text, int $minimumHeight = 28, string $from = 'A', string $to = 'H'): void
    {
        // Keep long handwritten transcriptions printable without Excel's 409-point row-height truncation.
        $lines = [];
        $width = $from === 'A' ? 155 : 115;
        foreach (preg_split('/\R/u', $text) ?: [''] as $paragraph) {
            while (mb_strlen($paragraph) > $width) {
                $space = mb_strrpos(mb_substr($paragraph, 0, $width + 1), ' ');
                $cut = $space ?: $width;
                $lines[] = mb_substr($paragraph, 0, $cut);
                $paragraph = ltrim(mb_substr($paragraph, $cut));
            }
            $lines[] = $paragraph;
        }
        foreach (array_chunk($lines, 12) as $chunk) {
            $this->merged($sheet, $row, implode("\n", $chunk), false, $from, $to);
            $sheet->getStyle("$from$row:$to$row")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('DCE1E8');
            $sheet->getRowDimension($row++)->setRowHeight(max($minimumHeight, count($chunk) * 16 + 8));
        }
    }
}
