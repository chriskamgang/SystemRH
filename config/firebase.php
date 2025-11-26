<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Credentials
    |--------------------------------------------------------------------------
    |
    | Path to the Firebase service account JSON credentials file.
    | This file is required for sending push notifications via FCM.
    |
    */

    'credentials' => env('FIREBASE_CREDENTIALS'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Project ID
    |--------------------------------------------------------------------------
    |
    | Your Firebase project ID. This is optional and used for validation.
    |
    */

    'project_id' => env('FIREBASE_PROJECT_ID'),

];
