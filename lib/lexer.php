<?php
// This Lexer is part of SimpleTest (simpletest.sourceforge.net)
/**
 *  base include file for SimpleTest
 *  @package    SimpleTest
 *  @subpackage MockObjects
 *  @version    $Id: parser.php 1723 2008-04-08 00:34:10Z lastcraft $
 */

/**#@+
 * Lexer mode stack constants
 */
foreach (array('LEXER_ENTER', 'LEXER_MATCHED',
                'LEXER_UNMATCHED', 'LEXER_EXIT',
                'LEXER_SPECIAL') as $i => $constant) {
    if (! defined($constant)) {
        define($constant, $i + 1);
    }
}
/**#@-*/

/**
 *    Compounded regular expression. Any of
 *    the contained patterns could match and
 *    when one does, it's label is returned.
 *    @package SimpleTest
 *    @subpackage WebTester
 */
class ParallelRegex {
    private $patterns;
    private $labels;
    private $regex;
    private $case;

    /**
     *    Constructor. Starts with no patterns.
     *    @param boolean $case    True for case sensitive, false
     *                            for insensitive.
     *    @access public
     */
    function __construct($case) {
        $this->case = $case;
        $this->patterns = array();
        $this->labels = array();
        $this->regex = null;
    }

    /**
     *    Adds a pattern with an optional label.
     *    @param string $pattern      Perl style regex, but ( and )
     *                                lose the usual meaning.
     *    @param string $label        Label of regex to be returned
     *                                on a match.
     *    @access public
     */
    function addPattern($pattern, $label = true) {
        $count = count($this->patterns);
        $this->patterns[$count] = $pattern;
        $this->labels[$count] = $label;
        $this->regex = null;
    }

    /**
     *    Attempts to match all patterns at once against
     *    a string. Matching starts at an offset rather than
     *    on a substring, so that ^ and lookbehinds still
     *    see the text before it.
     *    @param string $subject      String to match against.
     *    @param string $match        First matched portion of
     *                                subject.
     *    @param integer $offset      Byte offset to start at.
     *    @param integer $position    Byte offset of the match.
     *    @return boolean             True on success.
     *    @access public
     */
    function match($subject, &$match, $offset = 0, &$position = null) {
        if (count($this->patterns) == 0) {
            return false;
        }
        if (!preg_match($this->getCompoundedRegex(), $subject, $matches, PREG_OFFSET_CAPTURE, $offset)) {
            $match = '';
            return false;
        }
        list($match, $position) = $matches[0];
        for ($i = 1; $i < count($matches); $i++) {
            if ($matches[$i][0]) {
                return $this->labels[$i - 1];
            }
        }
        return true;
    }

    /**
     *    Compounds the patterns into a single
     *    regular expression separated with the
     *    "or" operator. Caches the regex.
     *    Will automatically escape (, ) and / tokens.
     *    @param array $patterns    List of patterns in order.
     *    @access private
     */
    protected function getCompoundedRegex() {
        if ($this->regex == null) {
            for ($i = 0, $count = count($this->patterns); $i < $count; $i++) {
				$this->patterns[$i] = '('.str_replace(
						['/', '(', ')'],
						['/', '(', ')'],
						$this->patterns[$i]
				).')';
            }
			$this->regex = '/'.implode('|', $this->patterns).'/'.$this->getPerlMatchingFlags();
        }
        return $this->regex;
    }

    /**
     *    Accessor for perl regex mode flags to use.
     *    @return string       Perl regex flags.
     *    @access private
     */
    protected function getPerlMatchingFlags() {
        return ($this->case ? "msS" : "msSi");
    }
}

/**
 *    States for a stack machine.
 *    @package SimpleTest
 *    @subpackage WebTester
 */
class SimpleStateStack {
    private $stack;

    /**
     *    Constructor. Starts in named state.
     *    @param string $start        Starting state name.
     *    @access public
     */
    function __construct($start) {
        $this->stack = array($start);
    }

    /**
     *    Accessor for current state.
     *    @return string       State.
     *    @access public
     */
    function getCurrent() {
        return $this->stack[count($this->stack) - 1];
    }

    /**
     *    Adds a state to the stack and sets it
     *    to be the current state.
     *    @param string $state        New state.
     *    @access public
     */
    function enter($state) {
        array_push($this->stack, $state);
    }

    /**
     *    Leaves the current state and reverts
     *    to the previous one.
     *    @return boolean    False if we drop off
     *                       the bottom of the list.
     *    @access public
     */
    function leave() {
        if (count($this->stack) == 1) {
            return false;
        }
        array_pop($this->stack);
        return true;
    }
}

/**
 *    Accepts text and breaks it into tokens.
 *    Some optimisation to make the sure the
 *    content is only scanned by the PHP regex
 *    parser once. Lexer modes must not start
 *    with leading underscores.
 *    @package SimpleTest
 *    @subpackage WebTester
 */
class SimpleLexer {
    private $regexes;
    private $parser;
    private $mode;
    private $mode_handlers;
    private $mode_functions;
    private $line_start_modes;
    private $case;

    /**
     *    Sets up the lexer in case insensitive matching
     *    by default.
     *    @param SimpleSaxParser $parser  Handling strategy by
     *                                    reference.
     *    @param string $start            Starting handler.
     *    @param boolean $case            True for case sensitive.
     *    @access public
     */
    function __construct($parser, $start = "accept", $case = false) {
        $this->case = $case;
        $this->regexes = array();
        $this->parser = $parser;
        $this->mode = new SimpleStateStack($start);
        $this->mode_handlers = array($start => $start);
		$this->mode_functions = array();
		$this->line_start_modes = array();
    }

    /**
     *    Adds a token search pattern for a particular
     *    parsing mode. The pattern does not change the
     *    current mode.
     *    @param string $pattern      Perl style regex, but ( and )
     *                                lose the usual meaning.
     *    @param string $mode         Should only apply this
     *                                pattern when dealing with
     *                                this type of input.
     *    @access public
     */
    function addPattern($pattern, $mode = "accept") {
        if (! isset($this->regexes[$mode])) {
            $this->regexes[$mode] = new ParallelRegex($this->case);
        }
        $this->regexes[$mode]->addPattern($pattern);
        if (! isset($this->mode_handlers[$mode])) {
            $this->mode_handlers[$mode] = $mode;
        }
    }

    /**
     *    Adds a pattern that will enter a new parsing
     *    mode. Useful for entering parenthesis, strings,
     *    tags, etc.
     *    @param string $pattern      Perl style regex, but ( and )
     *                                lose the usual meaning.
     *    @param string $mode         Should only apply this
     *                                pattern when dealing with
     *                                this type of input.
     *    @param string $new_mode     Change parsing to this new
     *                                nested mode.
     *    @access public
     */
    function addEntryPattern($pattern, $mode, $new_mode) {
        if (! isset($this->regexes[$mode])) {
            $this->regexes[$mode] = new ParallelRegex($this->case);
        }
        $this->regexes[$mode]->addPattern($pattern, $new_mode);
        if (! isset($this->mode_handlers[$new_mode])) {
            $this->mode_handlers[$new_mode] = $new_mode;
        }
    }

    /**
     *    Adds a pattern that will exit the current mode
     *    and re-enter the previous one.
     *    @param string $pattern      Perl style regex, but ( and )
     *                                lose the usual meaning.
     *    @param string $mode         Mode to leave.
     *    @access public
     */
    function addExitPattern($pattern, $mode) {
        if (! isset($this->regexes[$mode])) {
            $this->regexes[$mode] = new ParallelRegex($this->case);
        }
        $this->regexes[$mode]->addPattern($pattern, "__exit");
        if (! isset($this->mode_handlers[$mode])) {
            $this->mode_handlers[$mode] = $mode;
        }
    }

    /**
     *    Adds a pattern that has a special mode. Acts as an entry
     *    and exit pattern in one go, effectively calling a special
     *    parser handler for this token only.
     *    @param string $pattern      Perl style regex, but ( and )
     *                                lose the usual meaning.
     *    @param string $mode         Should only apply this
     *                                pattern when dealing with
     *                                this type of input.
     *    @param string $special      Use this mode for this one token.
     *    @access public
     */
    function addSpecialPattern($pattern, $mode, $special) {
        if (! isset($this->regexes[$mode])) {
            $this->regexes[$mode] = new ParallelRegex($this->case);
        }
        $this->regexes[$mode]->addPattern($pattern, "_$special");
        if (! isset($this->mode_handlers[$special])) {
            $this->mode_handlers[$special] = $special;
        }
    }

    /**
     *    Adds a mapping from a mode to another handler.
     *    @param string $mode        Mode to be remapped.
     *    @param string $handler     New target handler.
     *    @access public
     */
    function mapHandler($mode, $handler) {
        $this->mode_handlers[$mode] = $handler;
    }

	function mapFunction($mode, $function) {
		$this->mode_functions[$mode] = $function;
    }

    /**
     *    Marks a mode whose entry and exit tokens end
     *    the current line, so that patterns anchored
     *    with ^ can match straight after them. Elsewhere
     *    ^ only matches at the start of a real line.
     *    @param string $mode         Mode to mark.
     *    @access public
     */
    function addLineStartMode($mode) {
        $this->line_start_modes[$mode] = true;
    }

    /**
     *    Splits the page text into tokens. Will fail
     *    if the handlers report an error or if no
     *    content is consumed. If successful then each
     *    unparsed and parsed token invokes a call to the
     *    held listener.
     *    @param string $raw        Raw HTML text.
     *    @return boolean           True on success, else false.
     *    @access public
     */
    function parse($raw) {
        if (! isset($this->parser)) {
            return false;
        }
        $length = strlen($raw);
        $offset = 0;
        $line_start = false;
        while (is_array($parsed = $this->reduce($raw, $offset, $line_start))) {
            list($next, $unmatched, $matched, $mode) = $parsed;
            $line_start = $this->isLineStartToken($mode);
            if (! $this->dispatchTokens($unmatched, $matched, $mode)) {
                return false;
            }
            if ($next == $length) {
                return true;
            }
            if ($next == $offset) {
                return false;
            }
            $offset = $next;
        }
        if (! $parsed) {
            return false;
        }
        return $this->invokeParser(substr($raw, $offset), LEXER_UNMATCHED);
    }

    /**
     *    Sends the matched token and any leading unmatched
     *    text to the parser changing the lexer to a new
     *    mode if one is listed.
     *    @param string $unmatched    Unmatched leading portion.
     *    @param string $matched      Actual token match.
     *    @param string $mode         Mode after match. A boolean
     *                                false mode causes no change.
     *    @return boolean             False if there was any error
     *                                from the parser.
     *    @access private
     */
    protected function dispatchTokens($unmatched, $matched, $mode = false) {
        if (! $this->invokeParser($unmatched, LEXER_UNMATCHED)) {
            return false;
        }
        if (is_bool($mode)) {
            return $this->invokeParser($matched, LEXER_MATCHED);
        }
        if ($this->isModeEnd($mode)) {
            if (! $this->invokeParser($matched, LEXER_EXIT)) {
                return false;
            }
            return $this->mode->leave();
        }
        if ($this->isSpecialMode($mode)) {
            $this->mode->enter($this->decodeSpecial($mode));
            if (! $this->invokeParser($matched, LEXER_SPECIAL)) {
                return false;
            }
            return $this->mode->leave();
        }
        $this->mode->enter($mode);
        return $this->invokeParser($matched, LEXER_ENTER);
    }

    /**
     *    Tests to see if the new mode is actually to leave
     *    the current mode and pop an item from the matching
     *    mode stack.
     *    @param string $mode    Mode to test.
     *    @return boolean        True if this is the exit mode.
     *    @access private
     */
    protected function isModeEnd($mode) {
        return ($mode === "__exit");
    }

    /**
     *    Test to see if the mode is one where this mode
     *    is entered for this token only and automatically
     *    leaves immediately afterwoods.
     *    @param string $mode    Mode to test.
     *    @return boolean        True if this is the exit mode.
     *    @access private
     */
    protected function isSpecialMode($mode) {
        return (strncmp($mode, "_", 1) == 0);
    }

    /**
     *    Strips the magic underscore marking single token
     *    modes.
     *    @param string $mode    Mode to decode.
     *    @return string         Underlying mode name.
     *    @access private
     */
    protected function decodeSpecial($mode) {
        return substr($mode, 1);
    }

    /**
     *    Tests to see if a token enters or leaves a line
     *    start mode, so that the text after it begins a
     *    new line. Must be called before the token is
     *    dispatched, while the mode it leaves is current.
     *    @param string $mode    Mode after match.
     *    @return boolean        True if a new line follows.
     *    @access private
     */
    protected function isLineStartToken($mode) {
        if ($this->isModeEnd($mode)) {
            $mode = $this->mode->getCurrent();
        }
        return is_string($mode) && isset($this->line_start_modes[$mode]);
    }

    /**
     *    Calls the parser method named after the current
     *    mode. Empty content will be ignored. The lexer
     *    has a parser handler for each mode in the lexer.
     *    @param string $content        Text parsed.
     *    @param boolean $is_match      Token is recognised rather
     *                                  than unparsed data.
     *    @access private
     */
    protected function invokeParser($content, $is_match) {
        if (($content === '') || ($content === false)) {
            return true;
        }
        $handler = $this->mode_handlers[$this->mode->getCurrent()];
		if (isset($this->mode_functions[$handler])) {
			return call_user_func($this->mode_functions[$handler], $content, $is_match);
		}
        return $this->parser->$handler($content, $is_match);
    }

    /**
     *    Tries to match a chunk of text from an offset and
     *    if successful steps over the recognised chunk and
     *    any leading unparsed data. Empty strings will not
     *    be matched.
     *    @param string $raw         The subject to parse.
     *    @param integer $offset     Byte offset of the content
     *                               still to be eaten.
     *    @param boolean $line_start True if the content starts a
     *                               new line, even though the text
     *                               before it does not end one.
     *    @return array/boolean      Offset after the recognised
     *                               token, followed by unparsed
     *                               content, the recognised token
     *                               and finally the action the
     *                               parser is to take.
     *                               True if no match, false if there
     *                               is a parsing error.
     *    @access private
     */
    protected function reduce($raw, $offset, $line_start) {
        $regex = $this->regexes[$this->mode->getCurrent()];
        if ($line_start) {
            // Match the content on its own, so that ^ matches at its start.
            if ($action = $regex->match(substr($raw, $offset), $match, 0, $position)) {
                $position += $offset;
            }
        } else {
            $action = $regex->match($raw, $match, $offset, $position);
        }
        if ($action) {
            $unparsed = substr($raw, $offset, $position - $offset);
            return array($position + strlen($match), $unparsed, $match, $action);
        }
        return true;
    }
}
?>
