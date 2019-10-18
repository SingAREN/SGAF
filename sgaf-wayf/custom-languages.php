<?php // Copyright (c) 2019, SWITCH

// Localized language strings for SWITCHwayf
// Make sure to use HTML entities instead of plain UTF-8 characters for 
// non-ASCII characters if you are using the Embedded WAYF. It could be that the
// Embedded WAYF is used on non-UTF8 web pages, which then could cause 
// encoding issues

// *********************************************************************************
// If you want locales in your own language here, please send them to aai@switch.ch
// *********************************************************************************
// ****************************
//       English, default
// ****************************

// To permanently customize locales such that they are not overwritten by updates
// of the SWITCHwayf, create a file 'custom-languages.php' and override any 
// individual locale in the $langStrings array. For example like this:
// 
// $langStrings['en']['about_federation'] = 'About Example Federation';
// $langStrings['en']['additional_info'] = 'My <b>sample HTML content</b>';
// 
//
// Set a locale to an empty string ('') in order to hide it
// Note that any string in custom-languages.php will survive updates

// In particular you might want to override these three locales or set the
// to an empty string in order to hide them if they are not needed.
$langStrings['en']['about_federation'] = 'About Federation';  // This string can be hidden by setting it to ''
$langStrings['en']['about_organisation'] = 'About Federation Operator'; // This string can be hidden by setting it to ''
$langStrings['en']['additional_info'] = ''; // This string can be hidden by setting it to ''
