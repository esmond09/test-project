<?php

namespace App\Jobs;

use App\Models\FileUpload;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessCsvUpload implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public FileUpload $fileUpload
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Update status to processing
            $this->fileUpload->update(['status' => 'processing']);

            $filePath = storage_path('app/' . $this->fileUpload->filename);

            if (!file_exists($filePath)) {
                throw new Exception('File not found: ' . $filePath);
            }

            // Open and read CSV
            $file = fopen($filePath, 'r');

            // Read header row
            $headers = fgetcsv($file);

            // Clean headers (remove BOM and trim)
            $headers = array_map(function($header) {
                return trim($this->cleanUtf8(strtoupper($header)));
            }, $headers);

            // Map CSV columns
            $columnMap = [
                'UNIQUE_KEY' => 'unique_key',
                'PRODUCT_TITLE' => 'product_title',
                'PRODUCT_DESCRIPTION' => 'product_description',
                'STYLE#' => 'style',
                'SANMAR_MAINFRAME_COLOR' => 'sanmar_mainframe_color',
                'SIZE' => 'size',
                'COLOR_NAME' => 'color_name',
                'PIECE_PRICE' => 'piece_price',
            ];

            $totalRows = 0;
            $processedRows = 0;

            // Count total rows first
            while (fgetcsv($file) !== false) {
                $totalRows++;
            }

            $this->fileUpload->update(['total_rows' => $totalRows]);

            // Reset file pointer
            fseek($file, 0);
            fgetcsv($file); // Skip header again

            // Process each row
            while (($row = fgetcsv($file)) !== false) {
                try {
                    $data = [];

                    foreach ($headers as $index => $header) {
                        if (isset($columnMap[$header]) && isset($row[$index])) {
                            $value = $this->cleanUtf8($row[$index]);
                            $data[$columnMap[$header]] = $value !== '' ? $value : null;
                        }
                    }

                    // Validate required fields
                    if (empty($data['unique_key']) || empty($data['product_title'])) {
                        continue;
                    }

                    // UPSERT - update or insert based on unique_key
                    Product::updateOrCreate(
                        ['unique_key' => $data['unique_key']],
                        $data
                    );

                    $processedRows++;

                    // Update progress periodically
                    if ($processedRows % 100 === 0) {
                        $this->fileUpload->update(['processed_rows' => $processedRows]);
                    }
                } catch (Exception $e) {
                    Log::error('Error processing CSV row: ' . $e->getMessage());
                    continue;
                }
            }

            fclose($file);

            // Final update
            $this->fileUpload->update([
                'status' => 'completed',
                'processed_rows' => $processedRows,
            ]);

        } catch (Exception $e) {
            Log::error('CSV Processing failed: ' . $e->getMessage());

            $this->fileUpload->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clean non-UTF-8 characters from string
     */
    private function cleanUtf8(string $string): string
    {
        // Remove BOM
        $string = str_replace("\xEF\xBB\xBF", '', $string);

        // Convert to UTF-8 and remove invalid characters
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');

        // Remove any remaining non-UTF-8 characters
        $string = iconv('UTF-8', 'UTF-8//IGNORE', $string);

        return trim($string);
    }
}
