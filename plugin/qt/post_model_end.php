function markdown($text) {
#
# Initialize the parser and return the result of its transform method.
#
	# Setup static parser variable.
	static $parser;
	if (!isset($parser)) {
		include BBS_PATH . 'plugin/qt/markdown.php';
		$parser_class = MARKDOWN_PARSER_CLASS;
		$parser = new $parser_class;
	}

	# Transform text using parser.
	return $parser->transform($text);
}