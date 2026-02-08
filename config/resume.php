<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Job description
    |--------------------------------------------------------------------------
    */
    'job_description' => [
        'max_length' => (int) env('RESUME_JOB_DESCRIPTION_MAX_LENGTH', 50000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Resume upload (original)
    |--------------------------------------------------------------------------
    */
    'upload' => [
        'max_size' => (int) env('RESUME_UPLOAD_MAX_SIZE', 10240), // KB
        'mimes' => ['pdf', 'html', 'htm', 'txt'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Text extraction
    |--------------------------------------------------------------------------
    */
    'min_extracted_text_length' => (int) env('RESUME_MIN_EXTRACTED_TEXT_LENGTH', 50),

    /*
    |--------------------------------------------------------------------------
    | Generated resume default filename (base; date suffix added in controller)
    |--------------------------------------------------------------------------
    */
    'generated_filename' => env('RESUME_GENERATED_FILENAME', 'resume.html'),

];
