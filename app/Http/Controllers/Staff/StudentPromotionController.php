<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\StudentPromotionBatch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentPromotionController extends Controller
{
    /**
     * Display the promotion upload and preview page.
     */
    public function index()
    {
        $preview = session('promotion_preview');
        $batches = StudentPromotionBatch::with('admin')->latest()->take(10)->get();

        return view('staff.promotions.index', compact('preview', 'batches'));
    }

    /**
     * Download sample CSV template.
     */
    public function downloadSample(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="sample_student_promotion.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['student_number', 'year_level']);
            fputcsv($handle, ['2023-0001', '2nd']);
            fputcsv($handle, ['2023-0002', '3rd']);
            fputcsv($handle, ['2023-0003', '4th']);
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Process uploaded CSV and generate preview.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'max:2048'], // 2MB
        ]);

        $file = $request->file('csv_file');

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension !== 'csv') {
            return back()->withErrors(['csv_file' => 'The uploaded file must be a .csv file.']);
        }

        $content = file_get_contents($file->getRealPath());

        // Validate UTF-8 encoding
        if (! mb_check_encoding($content, 'UTF-8')) {
            return back()->withErrors(['csv_file' => 'The uploaded CSV file is not valid UTF-8 encoded.']);
        }

        // Strip UTF-8 BOM if present
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        // Parse CSV stream
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        // Header check
        $header = fgetcsv($stream);
        if (! $header || count($header) < 2) {
            fclose($stream);

            return back()->withErrors(['csv_file' => 'The CSV file header is invalid. The header row must be exactly: student_number,year_level']);
        }

        $header = array_map(fn ($col) => trim((string) $col), $header);
        if ($header !== ['student_number', 'year_level']) {
            fclose($stream);

            return back()->withErrors(['csv_file' => 'Invalid header row. The header row MUST be exactly: student_number,year_level']);
        }

        // First pass: collect all rows and candidate student numbers
        $rawRows = [];
        $rowNum = 1;
        $candidateNumbers = [];

        while (($row = fgetcsv($stream)) !== false) {
            $rowNum++;

            // Skip entirely empty lines
            if (count($row) === 1 && ($row[0] === null || trim($row[0]) === '')) {
                continue;
            }

            $rawStudentNumber = isset($row[0]) ? trim((string) $row[0]) : '';
            $rawYearLevel = isset($row[1]) ? trim((string) $row[1]) : '';

            $rawRows[] = [
                'row_number' => $rowNum,
                'student_number' => $rawStudentNumber,
                'year_level' => $rawYearLevel,
            ];

            if ($rawStudentNumber !== '') {
                $candidateNumbers[] = $rawStudentNumber;
            }
        }
        fclose($stream);

        if (empty($rawRows)) {
            return back()->withErrors(['csv_file' => 'The uploaded CSV file contains no data rows.']);
        }

        // Bulk load all matching students to avoid N+1 queries during validation
        $existingStudents = User::where('role', 'student')
            ->whereIn('student_id', array_unique($candidateNumbers))
            ->get()
            ->keyBy('student_id');

        $validYearLevels = ['1st', '2nd', '3rd', '4th'];
        $seenStudentNumbers = [];
        $validRows = [];
        $invalidRows = [];

        foreach ($rawRows as $item) {
            $rNum = $item['row_number'];
            $stNum = $item['student_number'];
            $yrLevel = $item['year_level'];

            // 1. Check blank cells
            if ($stNum === '' || $yrLevel === '') {
                $invalidRows[] = [
                    'row_number' => $rNum,
                    'student_number' => $stNum ?: '(blank)',
                    'year_level' => $yrLevel ?: '(blank)',
                    'reason' => 'Row contains blank cell(s). Both student_number and year_level are required.',
                ];

                continue;
            }

            // 2. CSV Injection protection: check if cell starts with =, +, -, @
            // Note: student_numbers starting with digits are safe. A cell starting with =, +, -, @ is risky.
            if ($this->isFormulaInjection($stNum) || $this->isFormulaInjection($yrLevel)) {
                $invalidRows[] = [
                    'row_number' => $rNum,
                    'student_number' => $stNum,
                    'year_level' => $yrLevel,
                    'reason' => 'Cell starts with forbidden formula character (=, +, -, @) leading to CSV injection risk.',
                ];

                continue;
            }

            // 3. Duplicate student number in file check
            if (isset($seenStudentNumbers[$stNum])) {
                $invalidRows[] = [
                    'row_number' => $rNum,
                    'student_number' => $stNum,
                    'year_level' => $yrLevel,
                    'reason' => "Duplicate student number in file (first seen at row {$seenStudentNumbers[$stNum]}).",
                ];

                continue;
            }
            $seenStudentNumbers[$stNum] = $rNum;

            // 4. Valid year level check
            if (! in_array($yrLevel, $validYearLevels, true)) {
                $invalidRows[] = [
                    'row_number' => $rNum,
                    'student_number' => $stNum,
                    'year_level' => $yrLevel,
                    'reason' => "Invalid year level '{$yrLevel}'. Must be one of: 1st, 2nd, 3rd, 4th.",
                ];

                continue;
            }

            // 5. Student exists in this system check
            $student = $existingStudents->get($stNum);
            if (! $student) {
                $invalidRows[] = [
                    'row_number' => $rNum,
                    'student_number' => $stNum,
                    'year_level' => $yrLevel,
                    'reason' => "Student number '{$stNum}' does not exist in the student records.",
                ];

                continue;
            }

            // Row is valid!
            $validRows[] = [
                'row_number' => $rNum,
                'user_id' => $student->id,
                'student_number' => $stNum,
                'student_name' => "{$student->last_name}, {$student->first_name}".($student->middle_initial ? " {$student->middle_initial}." : ''),
                'program' => $student->program ?: 'N/A',
                'current_year' => $student->year_level ?: 'None',
                'new_year' => $yrLevel,
            ];
        }

        $safeFileName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $file->getClientOriginalName());

        session()->put('promotion_preview', [
            'file_name' => $safeFileName,
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
        ]);

        return redirect()->route('admin.promotions.index')->with('info', 'File processed. Review the preview below before confirming.');
    }

    /**
     * Download invalid rows as a CSV file.
     */
    public function downloadInvalidCsv(): StreamedResponse|RedirectResponse
    {
        $preview = session('promotion_preview');
        $invalidRows = $preview['invalid_rows'] ?? [];

        if (empty($invalidRows)) {
            return back()->with('info', 'No invalid rows to download.');
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="invalid_promotion_rows.csv"',
        ];

        return response()->stream(function () use ($invalidRows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['row_number', 'student_number', 'year_level', 'reason']);

            foreach ($invalidRows as $row) {
                fputcsv($handle, [
                    $row['row_number'],
                    $this->sanitizeCsvCell($row['student_number']),
                    $this->sanitizeCsvCell($row['year_level']),
                    $this->sanitizeCsvCell($row['reason']),
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Clear current preview.
     */
    public function clearPreview()
    {
        session()->forget('promotion_preview');

        return redirect()->route('admin.promotions.index')->with('info', 'Promotion preview cleared.');
    }

    /**
     * Confirm and execute the promotion batch in transactions and chunks.
     */
    public function confirmPromotion(Request $request)
    {
        $preview = session('promotion_preview');

        if (! $preview || empty($preview['valid_rows'])) {
            return redirect()->route('admin.promotions.index')->withErrors(['error' => 'No valid rows to apply. Please upload a CSV file first.']);
        }

        $validRows = $preview['valid_rows'];
        $invalidRows = $preview['invalid_rows'] ?? [];
        $fileName = $preview['file_name'] ?? 'promotion.csv';

        DB::transaction(function () use ($validRows, $invalidRows, $fileName) {
            $chunks = array_chunk($validRows, 200);

            foreach ($chunks as $chunk) {
                $userIds = array_column($chunk, 'user_id');

                // Retrieve existing records to log old year levels and sections
                $students = User::whereIn('id', $userIds)->get()->keyBy('id');

                // Group by target new_year to execute minimal bulk updates
                $groupedByNewYear = [];
                $auditRecords = [];

                foreach ($chunk as $item) {
                    $uId = $item['user_id'];
                    $newYear = $item['new_year'];
                    $groupedByNewYear[$newYear][] = $uId;

                    $student = $students->get($uId);
                    if ($student) {
                        $auditRecords[] = [
                            'user_id' => $student->id,
                            'actor_id' => auth()->id(),
                            'action' => 'csv_promotion',
                            'old_year_level' => $student->year_level,
                            'new_year_level' => $newYear,
                            'old_section' => $student->section,
                            'new_section' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                // Bulk update users grouped by new year level (no row-by-row queries)
                foreach ($groupedByNewYear as $targetYear => $ids) {
                    DB::table('users')
                        ->whereIn('id', $ids)
                        ->where('role', 'student')
                        ->update([
                            'year_level' => $targetYear,
                            'section' => '', // Clear old section
                            'needs_section_confirmation' => true, // Mark student for prompt
                            'year_level_confirmed' => true,
                            'updated_at' => now(),
                        ]);
                }

                // Bulk insert audit logs
                if (! empty($auditRecords)) {
                    DB::table('year_level_audit_logs')->insert($auditRecords);
                }
            }

            // Log batch record
            StudentPromotionBatch::create([
                'admin_id' => auth()->id(),
                'file_name' => $fileName,
                'rows_applied' => count($validRows),
                'rows_skipped' => count($invalidRows),
                'invalid_rows_data' => $invalidRows,
            ]);
        });

        session()->forget('promotion_preview');

        $msg = 'Successfully promoted '.count($validRows).' students.';
        if (count($invalidRows) > 0) {
            $msg .= ' '.count($invalidRows).' invalid rows were skipped.';
        }

        return redirect()->route('admin.promotions.index')->with('success', $msg);
    }

    /**
     * Check if a string begins with dangerous CSV formula prefix characters.
     */
    private function isFormulaInjection(string $val): bool
    {
        if ($val === '') {
            return false;
        }

        $firstChar = $val[0];

        return in_array($firstChar, ['=', '+', '-', '@'], true);
    }

    /**
     * Sanitize output cell value to prevent CSV injection when opened in spreadsheet programs.
     */
    private function sanitizeCsvCell(mixed $val): string
    {
        $str = (string) $val;
        if ($this->isFormulaInjection($str)) {
            return "'".$str;
        }

        return $str;
    }
}
