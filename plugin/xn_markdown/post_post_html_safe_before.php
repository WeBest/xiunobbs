$message = core::gpc('message', 'P');
$message = xn_markdown::markdown2html($message);