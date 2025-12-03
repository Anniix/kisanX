<?php
// This file MUST be included after session_start() on any page

// 1. Define available languages and a default
$available_langs = [
    'en' => 'English',
    'hi' => 'हिन्दी',
    'mr' => 'मराठी'
];
$default_lang = 'en';

// 2. Check if user is switching language
if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $available_langs)) {
    // User selected a new, valid language
    $_SESSION['lang'] = $_GET['lang'];
}

// 3. Determine which language to load
// Use session language if set, otherwise use the default
$current_lang = $_SESSION['lang'] ?? $default_lang;

// 4. Load the language file
// Ensure the selected language file exists, if not, default to 'en'
$lang_file = __DIR__ . '/../lang/' . $current_lang . '.php';

if (!file_exists($lang_file)) {
    // Fallback to English if a language file is missing
    $current_lang = $default_lang;
    $lang_file = __DIR__ . '/../lang/' . $default_lang . '.php';
}

// 5. Include the file. This creates the $lang array
// This array ($lang) is now available to any page that includes this file.
include_once($lang_file);
?>