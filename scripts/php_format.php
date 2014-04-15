<?php

if ( !empty( $argv[ 1 ] ) ) {
    $file = $argv[ 1 ];
    echo formatFile( $file );
}

class Token
{
    public $type;
    public $typeString;
    public $contents;
    protected static $constants;

    public function __construct( $rawToken )
    {
        $this->loadTokenConstants();
        if ( is_array( $rawToken ) ) {
            $this->type       = $rawToken[ 0 ];
            $this->contents   = $rawToken[ 1 ];
            $this->typeString = self::$constants[ $this->type ];
        } else {
            $this->type     = -1;
            $this->contents = $rawToken;
        }
    }

    protected function loadTokenConstants()
    {
        if ( null == self::$constants ) {
            $constants = get_defined_constants( true );
            self::$constants = array_flip( $constants[ 'tokenizer' ] );
//            var_dump($this->constants);die;
        }
    }
}

/**
 * @param int $j
 * @param int $i
 * @param array $tokens
 * @return Token
 */
function nextToken( &$j, $i, $tokens )
{
    $j     = $i;
    $token = new Token( '' );
    do {
        $j++;
        if ( !isset( $tokens[ $j ] ) ) {
            break;
        }
        $token = $tokens[ $j ];
    } while ( $token->type == T_WHITESPACE );
    return $token;
}

function isAssocArrayVariable($tokens, $i, $offset = 0 )
{
    $j = $i + $offset;
    return $tokens[ $j ]->type == T_VARIABLE &&
           $tokens[ $j + 1 ]->contents == '[' &&
           $tokens[ $j + 2 ]->type == T_STRING &&
           preg_match( '/[a-z_]+/', $tokens[ $j + 2 ]->contents ) &&
           $tokens[ $j + 3 ]->contents == ']';
}

/**
 * @param $file
 * @param $j
 * @return string
 */
function formatFile( $file, $j = 0 )
{
    $code = file_get_contents( $file );

    $rawTokens = token_get_all( $code );
    $tokens    = array();
    foreach ( $rawTokens as $rawToken ) {
        $tokens[ ] = new Token( $rawToken );
    }
    #echo count( $tokens ) . " tokens found\n";

    $OPERATORS = array( '=', '.', '+', '-', '*', '/', '%', '||', '&&', '+=', '-=', '*=', '/=', '.=', '%=', '==', '!=', '<=', '>=', '<', '>', '===', '!==' );

    $IMPORT_STATEMENTS = array( T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE );

    $CONTROL_STRUCTURES = array( T_IF, T_ELSEIF, T_FOREACH, T_FOR, T_WHILE, T_SWITCH, T_ELSE );
    $DECLARATION_STRUCTURES = array( T_ARRAY, T_FUNCTION, T_DECLARE );

    $WHITESPACE_BEFORE  = array( '?', '{', '=>', ']', ')' );
    $WHITESPACE_AFTER   = array( ',', '?', '=>', '[', '(' );

    foreach ( $OPERATORS as $op ) {
        $WHITESPACE_BEFORE[ ] = $op;
        $WHITESPACE_AFTER[ ]  = $op;
    }

    $matchingTernary        = false;
    $matchingControlOneLine = false;
    $inControlStatement     = false;
    $inHereDoc = false;
    $inDeclarationStatement = false;
    $controlNesting         = 0;

    $level = 0; // level of indentation
    $sep   = "    "; // 4 spaces
    $interpolation = false;

// First pass - filter out unwanted tokens
if (false) { // do not touch syntax
    $filteredTokens = array();
    for ( $i = 0, $n = count( $tokens ); $i < $n; $i++ ) {
        /* @var $token Token */
        $token = $tokens[ $i ];
        if ( $token->type == T_OPEN_TAG ) {
//        $level++; // uncomment if all code after open tag should be indented
        } elseif ( $token->type == T_WHITESPACE ) {
            if ( strpos( $token->contents, "\n" ) === 0 ) {
                // new line indentation, adding it to each new line
                $l = $level;
                if ( nextToken( $j, $i, $tokens )->contents == "}" ) {
                    $l--;
                }
                if ( !(nextToken( $j, $i, $tokens )->type == T_COMMENT) ) {
                    $content = str_replace(array(" ", "\t"), "", $token->contents);
                    $token->contents = $content . str_repeat( $sep, $l );
                }
            } elseif ( strpos( $token->contents, "\t" ) ) {
                // replace tab with spaces
                $count           = 0;
                $token->contents = str_replace( "\t", $sep, $token->contents, $count );
                if ( $count ) {
                    #echo $count, " tabs converted to spaces";
                }
            }
        } elseif ( $token->contents == "{" ) {
            if ( $token->type != T_CURLY_OPEN && $token->type != T_DOLLAR_OPEN_CURLY_BRACES ) { // variable interpolation in string {
                $level++;
                if ( strpos( $tokens[ $i + 1 ]->contents, "\n" ) === false && $level ) {
                    /*
                    //TODO: moves comments to next line
                    $token->contents .= "\n" . str_repeat( $sep, $level );
                    */
                }
            } else {
                $interpolation = true;
            }
        } elseif ( $token->contents == "}" ) {
            if ( !$interpolation ) {
                $level--;
                if ( $filteredTokens[ count( $filteredTokens ) - 1 ]->type != T_WHITESPACE ) {
                    $filteredTokens[ ] = new Token( array( T_WHITESPACE, "\n" . str_repeat( $sep, $level ) ) );
                    if ( $level ) {
                        $token->contents .= str_repeat( $sep, $level );
                    }
                }
            } else {
                $interpolation = false;
            }
        }

        if ( $token->contents == '?' ) {
            $matchingTernary = true;
        }
        if ( in_array( $token->type, $IMPORT_STATEMENTS ) && nextToken( $j, $i, $tokens )->contents == '(' ) {
            /*
            //TODO: breaks whitespace after import statements
            $filteredTokens[ ] = $token;
            if ( $tokens[ $i + 1 ]->type != T_WHITESPACE ) {
                $filteredTokens[ ] = new Token( array( T_WHITESPACE, ' ' ) );
            }
            $i = $j;
            do {
                $i++;
                $token = $tokens[ $i ];
                if ( $token->contents != ')' ) {
                    $filteredTokens[ ] = $token;
                }
            } while ( $token->contents != ')' );
            */
        } elseif ( $token->type == T_ELSE && nextToken( $j, $i, $tokens )->type == T_IF ) {
            $i                 = $j;
            $filteredTokens[ ] = new Token( array( T_ELSEIF, 'elseif' ) );
        } elseif ( in_array( $token->type, $CONTROL_STRUCTURES ) ) {
            $inControlStatement = true;
            $filteredTokens[ ]  = $token;
        } elseif ( in_array( $token->type, $DECLARATION_STRUCTURES ) ) {
            $inDeclarationStatement = true;
            $filteredTokens[ ]  = $token;
        } elseif ( $token->type == T_START_HEREDOC ) {
            $inHereDoc = true;
            $filteredTokens[ ]  = $token;
        } elseif ( $token->type == T_END_HEREDOC ) {
            $inHereDoc = false;
            $filteredTokens[ ]  = $token;
        } elseif ( $token->contents == '(' && $inControlStatement ) {
            $controlNesting++;
            $filteredTokens[ ] = $token;
        } elseif ( $token->contents == ')' && $inControlStatement ) {
            $controlNesting--;
            $filteredTokens[ ]  = $token;
            $inControlStatement = $controlNesting != 0;
            $nextToken          = nextToken( $j, $i, $tokens );
            if ( $controlNesting == 0 && $nextToken->contents != '{' && $nextToken->contents != ';' ) {
                // single line control to be wrapped between
                $matchingControlOneLine = true;
                //TODO: this doesn't work correctly
                /*
                $filteredTokens[ ]      = new Token( '{' );
                $level++;
                $filteredTokens[ ] = new Token( array( T_WHITESPACE, "\n" . str_repeat( $sep, $level ) ) );
                if ( isset( $tokens[ $i + 1 ] ) ) {
                    $tokens[ $i + 1 ]->contents = trim( $tokens[ $i + 1 ]->contents );
                }
                */
            }
        } elseif ( $token->contents == ";" && $matchingControlOneLine ) {
            $matchingControlOneLine = false;
            $filteredTokens[ ]      = $token;
            /*
            $filteredTokens[ ] = new Token( array( T_WHITESPACE, "\n" . str_repeat( $sep, --$level ) ) );
            $filteredTokens[ ] = new Token( '}' );
            $filteredTokens[ ] = new Token( array( T_WHITESPACE, "\n" ) );
            */
        } elseif ( $token->contents == ':' ) {
            if ( $matchingTernary ) {
                $matchingTernary = false;
            } elseif ( $tokens[ $i - 1 ]->type == T_WHITESPACE ) {
                array_pop( $filteredTokens ); // Remove whitespace before
            }
            $filteredTokens[ ] = $token;
        } else {
            $filteredTokens[ ] = $token;
        }
    }
    $tokens = $filteredTokens;
}

// Second pass - add whitespace
    $output          = '';
    $matchingTernary = false;
    $doubleQuote     = false;
    for ( $i = 0, $n = count( $tokens ); $i < $n; $i++ ) {
        $first = $i == 0;
        $last  = ( $i + 1 == $n );
        $token = $tokens[ $i ];
        if ( $token->contents == '?' ) {
            $matchingTernary = true;
        }
        if ( $token->contents == '"' && isAssocArrayVariable( $tokens, $i, 1 ) && $tokens[ $i + 5 ]->contents == '"' ) {
            /*
             * Handle case where the only thing quoted is the assoc array variable.
             * Eg. "$value[key]"
             */
            $quote              = $tokens[ $i++ ]->contents;
            $var                = $tokens[ $i++ ]->contents;
            $openSquareBracket  = $tokens[ $i++ ]->contents;
            $str                = $tokens[ $i++ ]->contents;
            $closeSquareBracket = $tokens[ $i++ ]->contents;
            $quote              = $tokens[ $i ]->contents;
            $output .= $var . "['" . $str . "']";
            $doubleQuote = false;
            continue;
        }
        if ( $token->contents == '"' ) {
            $doubleQuote = !$doubleQuote;
        }
        if ( $doubleQuote && $token->contents == '"' && isAssocArrayVariable( $tokens, $i, 1 ) ) {
            // don't $output .= "
        } elseif ( $doubleQuote && isAssocArrayVariable( $tokens, $i ) ) {
            if ( !$first && $tokens[ $i - 1 ]->contents != '"' ) {
                $output .= '" . ';
            }
            $var                = $token->contents;
            $openSquareBracket  = $tokens[ ++$i ]->contents;
            $str                = $tokens[ ++$i ]->contents;
            $closeSquareBracket = $tokens[ ++$i ]->contents;
            $output .= $var . "['" . $str . "']";
            if ( !$last && $tokens[ $i + 1 ]->contents != '"' ) {
                $output .= ' . "';
            } else {
                $i++; // process "
                $doubleQuote = false;
            }
        } elseif ( $token->type == T_STRING && ( $first || $tokens[ $i - 1 ]->contents == '[' )
                   && ( $last || $tokens[ $i + 1 ]->contents == ']' )
        ) {
            //TODO: converts also constants into strings?
            if ( false && preg_match( '/[a-z_]+/', $token->contents ) ) {
                $output .= "'" . $token->contents . "'";
            } else {
                $output .= $token->contents;
            }
        } elseif ( $token->type == T_ENCAPSED_AND_WHITESPACE || $token->type == T_STRING ) {
            $output .= $token->contents;
        } elseif ( $token->contents == '-' && ( $last || in_array(
                    $tokens[ $i + 1 ]->type,
                    array( T_LNUMBER, T_DNUMBER )
                ) )
        ) {
            $output .= '-';
        } elseif ( in_array( $token->type, $CONTROL_STRUCTURES ) ) {
            $output .= $token->contents;
            if ( $tokens[ $i + 1 ]->type != T_WHITESPACE ) {
                $output .= ' ';
            }
        } elseif ( !$doubleQuote && $token->contents == '}' && ( !$last && in_array( $tokens[ $i + 1 ]->type, $CONTROL_STRUCTURES ) ) ) {
            $output .= '} ';
        } elseif ( $token->contents == '=' && ( !$last && $tokens[ $i + 1 ]->contents == '&' ) ) {
            if ( !$first && $tokens[ $i - 1 ]->type != T_WHITESPACE ) {
                $output .= ' ';
            }
            $i++; // match &
            $output .= '=&';
            if ( !$last && $tokens[ $i + 1 ]->type != T_WHITESPACE ) {
                $output .= ' ';
            }
        } elseif ( $token->contents == ':' && $matchingTernary ) {
            $matchingTernary = false;
            if ( !$first && $tokens[ $i - 1 ]->type != T_WHITESPACE ) {
                $output .= ' ';
            }
            $output .= ':';
            if ( !$last && $tokens[ $i + 1 ]->type != T_WHITESPACE ) {
                $output .= ' ';
            }
        } elseif ( !$doubleQuote && in_array(
                       $token->contents,
                       $WHITESPACE_BEFORE
                   ) && !$first && $tokens[ $i - 1 ]->type != T_WHITESPACE &&
                   in_array( $token->contents, $WHITESPACE_AFTER ) && !$last && $tokens[ $i + 1 ]->type != T_WHITESPACE
        ) {
            $output .= ' ' . $token->contents . ' ';
        } elseif ( !$doubleQuote && in_array(
                       $token->contents,
                       $WHITESPACE_BEFORE
                   ) && !$first && $tokens[ $i - 1 ]->type != T_WHITESPACE
        ) {
            $output .= ' ' . $token->contents;
        } elseif ( !$doubleQuote && in_array(
                       $token->contents,
                       $WHITESPACE_AFTER
                   ) && !$last && $tokens[ $i + 1 ]->type != T_WHITESPACE
        ) {
            $output .= $token->contents . ' ';
        } else {
            $output .= $token->contents;
        }
    }

    $output = str_replace( array( '( )', '(  )', '[ ]', '[  ]' ), array( '()', '()', '[]', '[]' ), $output );

    if ( preg_replace( '#\s+#', '', $code ) !== preg_replace( '#\s+#', '', $output ) ) {
        $originalLines = explode( "\n", $code );
        $formattedLines = explode( "\n", $output );
        $error = "Lines do not match:\n";
        $j = -1;
        foreach ( $originalLines as $i => $line ) {
            $j++;
            if ( preg_replace( '#\s+#', '', $line ) === preg_replace( '#\s+#', '', $formattedLines[ $j ] ) ) {
                continue;
            }

            $error .= str_pad( $i, 5, ' ', STR_PAD_LEFT ) . ": - {$line}\n";
            $error .= str_pad( $j, 5, ' ', STR_PAD_LEFT ) . ": + {$formattedLines[$j]}\n";

            $origNextLine = preg_replace( '#\s+#', '', $originalLines [ $i + 1 ] );
            $formatNextChunk = array_slice($formattedLines, $j + 1, 5);
            $error .= "$origNextLine\n";
            while (!empty($formattedLines[ $j + 1 ]) && $origNextLine != preg_replace( '#\s+#', '', $formattedLines[ $j + 1 ] ) ) {
                $error .= str_pad( $j + 1, 5, ' ', STR_PAD_LEFT ) . ": + " . $formattedLines[ $j + 1 ] . "\n";
                $j++;
            }
            if ($j > $i + 10) {
                $error .= $formattedLines[ $j + 1 ];
                break;
            }
        }
        //throw new Exception($error);
    }

    return $output;
}

//formatFile( $file, $j );
