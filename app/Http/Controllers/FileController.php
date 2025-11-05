<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCsvUpload;
use App\Models\FileUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FileController extends Controller
{
    /**
     * Get all file uploads for the authenticated user
     */
    public function index()
    {
        $uploads = FileUpload::where('user_id', Auth::id())
            ->orWhereNull('user_id')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $uploads->map(function ($upload) {
                return [
                    'id' => $upload->id,
                    'original_filename' => $upload->original_filename,
                    'status' => $upload->status,
                    'total_rows' => $upload->total_rows,
                    'processed_rows' => $upload->processed_rows,
                    'error_message' => $upload->error_message,
                    'created_at' => $upload->created_at->toISOString(),
                    'updated_at' => $upload->updated_at->toISOString(),
                ];
            }),
        ]);
    }

    /**
     * Upload and process CSV file
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt', // Max 10MB
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $filename = 'uploads/' . Str::uuid() . '.csv';

        // Store the file
        $file->storeAs('', $filename);

        // Create file upload record
        $fileUpload = FileUpload::create([
            'filename' => $filename,
            'original_filename' => $originalName,
            'status' => 'pending',
            'user_id' => Auth::id(),
        ]);

        // Dispatch job to process CSV
        ProcessCsvUpload::dispatch($fileUpload);

        return response()->json([
            'message' => 'File uploaded successfully and queued for processing',
            'data' => [
                'id' => $fileUpload->id,
                'original_filename' => $fileUpload->original_filename,
                'status' => $fileUpload->status,
                'created_at' => $fileUpload->created_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * Get status of a specific upload
     */
    public function show($id)
    {
        $upload = FileUpload::findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $upload->id,
                'original_filename' => $upload->original_filename,
                'status' => $upload->status,
                'total_rows' => $upload->total_rows,
                'processed_rows' => $upload->processed_rows,
                'error_message' => $upload->error_message,
                'created_at' => $upload->created_at->toISOString(),
                'updated_at' => $upload->updated_at->toISOString(),
            ],
        ]);
    }
}
