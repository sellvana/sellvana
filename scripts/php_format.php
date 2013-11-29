<?php
class Token {
    public $type;
    public $contents;

    public function __construct( $rawToken ) {
        if ( is_array( $rawToken ) ) {
            $this->type = $rawToken[ 0 ];
            $this->contents = $rawToken[ 1 ];
        } else {
            $this->type = -1;
            $this->contents = $rawToken;
        }
    }
}

$file = $argv[ 1 ];
$code = file_get_contents( $file );

$rawTokens = token_get_all( $code );
$tokens = array();
foreach ( $rawTokens as $rawToken ) {
    $tokens[] = new Token( $rawToken );
}

function skipWhitespace( &$tokens, &$i ) {
    global $lineNo;
    $i++;
    $token = $tokens[ $i ];
    while ( $token->type == T_WHITESPACE ) {
        $lineNo += substr( $token->contents, "\n" );
        $i++;
        $token = $tokens[ $i ];
    }
}

function nextToken( &$j ) {
    global $tokens, $i;
    $j = $i;
    do {
        $j++;
        $token = $tokens[ $j ];
    } while ( $token->type == T_WHITESPACE );
    return $token;
}

$OPERATORS = array( '=', '.', '+', '-', '*', '/', '%', '||', '&&', '+=', '-=', '*=', '/=', '.=', '%=', '==', '!=', '<=', '>=', '<', '>', '===', '!==' );

$IMPORT_STATEMENTS = array( T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE );

$CONTROL_STRUCTURES = array( T_IF, T_ELSEIF, T_FOREACH, T_FOR, T_WHILE, T_SWITCH, T_ELSE );
$WHITESPACE_BEFORE = array( '?', '{', '=>', ']', ')' );
$WHITESPACE_AFTER = array( ',', '?', '=>', '[', '(' );

foreach ( $OPERATORS as $op ) {
    $WHITESPACE_BEFORE[] = $op;
    $WHITESPACE_AFTER[] = $op;
}

$matchingTernary = false;

// First pass - filter out unwanted tokens
$filteredTokens = array();
for ( $i = 0, $n = count( $tokens ); $i < $n; $i++ ) {
    $token = $tokens[ $i ];
    if ( $token->contents == '?' ) {
        $matchingTernary = true;
    }
    if ( in_array( $token->type, $IMPORT_STATEMENTS ) && nextToken( $j )->contents == '(' ) {
        $filteredTokens[] = $token;
        if ( $tokens[ $i + 1 ]->type != T_WHITESPACE ) {
            $filteredTokens[] = new Token( array( T_WHITESPACE, ' ' ) );
        }
        $i = $j;
        do {
            $i++;
            $token = $tokens[ $i ];
            if ( $token->contents != ')' ) {
                $filteredTokens[] = $token;
            }
        } while ( $token->contents != ')' );
    } elseif ( $token->type == T_ELSE && nextToken( $j )->type == T_IF ) {
        $i = $j;
        $filteredTokens[] = new Token( array( T_ELSEIF, 'elseif' ) );
    } elseif ( $token->contents == ':' ) {
        if ( $matchingTernary ) {
            $matchingTernary = false;
        } elseif ( $tokens[ $i - 1 ]->type == T_WHITESPACE ) {
            array_pop( $filteredTokens ); // Remove whitespace before
        }
        $filteredTokens[] = $token;
    } else {
        $filteredTokens[] = $token;
    }
}
$tokens = $filteredTokens;

function isAssocArrayVariable( $offset = 0 ) {
    global $tokens, $i;
    $j = $i + $offset;
    return $tokens[ $j ]->type == T_VARIABLE &&
        $tokens[ $j + 1 ]->contents == '[' &&
        $tokens[ $j + 2 ]->type == T_STRING &&
        preg_match( '/[a-z_]+/', $tokens[ $j + 2 ]->contents ) &&
        $tokens[ $j + 3 ]->contents == ']';
}

// Second pass - add whitespace
$output = '';
$matchingTernary = false;
$doubleQuote = false;
for ( $i = 0, $n = count( $tokens ); $i < $n; $i++ ) {
    $token = $tokens[ $i ];
    if ( $token->contents == '?' ) {
        $matchingTernary = true;
    }
    if ( $token->contents == '"' && isAssocArrayVariable( 1 ) && $tokens[ $i + 5 ]->contents == '"' ) {
        /*
         * Handle case where the only thing quoted is the assoc array variable.
         * Eg. "$value[key]"
         */
        $quote = $tokens[ $i++ ]->contents;
        $var = $tokens[ $i++ ]->contents;
        $openSquareBracket = $tokens[ $i++ ]->contents;
        $str = $tokens[ $i++ ]->contents;
        $closeSquareBracket = $tokens[ $i++ ]->contents;
        $quote = $tokens[ $i ]->contents;
        $output .= $var . "['" . $str . "']";
        $doubleQuote = false;
        continue;
    }
    if ( $token->contents == '"' ) {
        $doubleQuote = !$doubleQuote;
    }
    if ( $doubleQuote && $token->contents == '"' && isAssocArrayVariable( 1 ) ) {
        // don't $output .= "
    } elseif ( $doubleQuote && isAssocArrayVariable() ) {
        if ( $tokens[ $i - 1 ]->contents != '"' ) {
            $output .= '" . ';
        }
        $var = $token->contents;
        $openSquareBracket = $tokens[ ++$i ]->contents;
        $str = $tokens[ ++$i ]->contents;
        $closeSquareBracket = $tokens[ ++$i ]->contents;
        $output .= $var . "['" . $str . "']";
        if ( $tokens[ $i + 1 ]->contents != '"' ) {
            $output .= ' . "';
        } else {
            $i++; // process "
            $doubleQuote = false;
        }
    } elseif ( $token->type == T_STRING && $tokens[ $i - 1 ]->contents == '[' && $tokens[ $i + 1 ]->contents == ']' ) {
        if ( preg_match( '/[a-z_]+/', $token->contents ) ) {
            $output .= "'" . $token->contents . "'";
        } else {
            $output .= $token->contents;
        }
    } elseif ( $token->type == T_ENCAPSED_AND_WHITESPACE || $token->type == T_STRING ) {
        $output .= $token->contents;
    } elseif ( $token->contents == '-' && in_array( $tokens[ $i + 1 ]->type, array( T_LNUMBER, T_DNUMBER ) ) ) {
        $output .= '-';
    } elseif ( in_array( $token->type, $CONTROL_STRUCTURES ) ) {
        $output .= $token->contents;
        if ( $tokens[ $i + 1 ]->type != T_WHITESPACE ) {
            $output .= ' ';
        }
    } elseif ( $token->contents == '}' && in_array( $tokens[ $i + 1 ]->type, $CONTROL_STRUCTURES ) ) {
        $output .= '} ';
    } elseif ( $token->contents == '=' && $tokens[ $i + 1 ]->contents == '&' ) {
        if ( $tokens[ $i - 1 ]->type != T_WHITESPACE ) {
            $output .= ' ';
        }
        $i++; // match &
        $output .= '=&';
        if ( $tokens[ $i + 1 ]->type != T_WHITESPACE ) {
            $output .= ' ';
        }
    } elseif ( $token->contents == ':' && $matchingTernary ) {
        $matchingTernary = false;
        if ( $tokens[ $i - 1 ]->type != T_WHITESPACE ) {
            $output .= ' ';
        }
        $output .= ':';
        if ( $tokens[ $i + 1 ]->type != T_WHITESPACE ) {
            $output .= ' ';
        }
    } elseif ( in_array( $token->contents, $WHITESPACE_BEFORE ) && $tokens[ $i - 1 ]->type != T_WHITESPACE &&
        in_array( $token->contents, $WHITESPACE_AFTER ) && $tokens[ $i + 1 ]->type != T_WHITESPACE ) {
        $output .= ' ' . $token->contents . ' ';
    } elseif ( in_array( $token->contents, $WHITESPACE_BEFORE ) && $tokens[ $i - 1 ]->type != T_WHITESPACE ) {
        $output .= ' ' . $token->contents;
    } elseif ( in_array( $token->contents, $WHITESPACE_AFTER ) && $tokens[ $i + 1 ]->type != T_WHITESPACE ) {
        $output .= $token->contents . ' ';
    } else {
        $output .= $token->contents;
    }
}

$output = str_replace( array( '()', '[]' ), array( '()', '[]' ), $output );

echo $output;