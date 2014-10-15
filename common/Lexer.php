<?php
/**
 * Annotation lexical parser - adapted from doctrine project, see license and authors.
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\common;


/**
 * Class Lexer
 * @package webnula2\components
 */
final class Lexer extends \CComponent
{
	/**
	 *
	 */
	const AT_T = 1;
	/**
	 *
	 */
	const NS_T = 2;
	/**
	 *
	 */
	const INT_T = 3;
	/**
	 *
	 */
	const FLOAT_T = 4;
	/**
	 *
	 */
	const STRING_T = 5;
	/**
	 *
	 */
	const ID_T = 6;
	/**
	 *
	 */
	const NONE_T = 7;
	/**
	 *
	 */
	const NULL_T = 8;
	/**
	 *
	 */
	const FALSE_T = 9;
	/**
	 *
	 */
	const TRUE_T = 10;
	/**
	 *
	 */
	const OP_T = 11;
	/**
	 *
	 */
	const CP_T = 12;
	/**
	 *
	 */
	const OCB_T = 13;
	/**
	 *
	 */
	const CCB_T = 14;
	/**
	 *
	 */
	const CM_T = 15;
	/**
	 *
	 */
	const CL_T = 16;
	/**
	 *
	 */
	const EQ_T = 17;
	/**
	 * @var
	 */
	public $lookahead;
	/**
	 * @var
	 */
	public $token;
	/**
	 * @var array
	 */
	private $tokens = array();
	/**
	 * @var int
	 */
	private $cursor = 0;
	/**
	 * @var int
	 */
	private $peek = 0;

	/**
	 * @param $source
	 */
	public function scan( $source )
	{
		$this->tokens = array();
		$this->reset();

		$lines = preg_split( '/([a-z_][a-z0-9_]*)|((?:[+-]?[0-9]+(?:[\.][0-9]+)*)(?:[eE][+-]?[0-9]+)?)|("(?:[^"]|"")*")|\s+|\*+|(.)/i',
			$source, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE );

		foreach ( $lines as $line ) {
			$this->tokens[] = array(
				'type' => $this->fetchType( $line[0] ),
				'value' => $line[0],
				'offset' => $line[1]
			);
		}
	}

	/**
	 *
	 */
	public function reset()
	{
		$this->lookahead = null;
		$this->peek = 0;
		$this->cursor = 0;
		$this->token = null;
	}

	/**
	 * @param $str
	 *
	 * @return int
	 */
	private function fetchType( &$str )
	{
		if ( is_numeric( $str ) ) {
			if ( strpos( $str, '.' ) !== false || strpos( $str, 'e' ) !== false ) {
				$str = (float)$str;

				return self::FLOAT_T;
			} else {
				$str = (int)$str;

				return self::INT_T;
			}
		}

		if ( $str[0] === '"' ) {
			$str = str_replace( '""', '"', substr( $str, 1, strlen( $str ) - 2 ) );

			return self::STRING_T;
		} else {
			switch ( $str ) {
				case '@':
					return self::AT_T;
				case '\\':
					return self::NS_T;
				case '(':
					return self::OP_T;
				case ')':
					return self::CP_T;
				case '{':
					return self::OCB_T;
				case '}':
					return self::CCB_T;
				case ':':
					return self::CL_T;
				case ',':
					return self::CM_T;
				case '=':
					return self::EQ_T;
				default:
					switch ( strtolower( $str ) ) {
						case 'true':
							$str = (boolean)$str;

							return self::TRUE_T;
						case 'false':
							$str = (boolean)$str;

							return self::FALSE_T;
					}
					if ( ctype_alpha( $str[0] ) || $str[0] === '_' ) {
						return self::ID_T;
					}

					return self::NONE_T;
			}
		}
	}

	/**
	 * @param $token
	 *
	 * @return bool
	 */
	public function is( $token )
	{
		return isset( $this->lookahead ) && $this->lookahead['type'] === $token;
	}

	/**
	 * @param $tokens
	 *
	 * @return bool
	 */
	public function isAny( $tokens )
	{
		return isset( $this->lookahead ) && in_array( $this->lookahead['type'], $tokens, true );
	}

	/**
	 * @return bool
	 */
	public function next()
	{
		$this->peek = 0;
		$this->token = $this->lookahead;
		$this->lookahead = isset( $this->tokens[$this->cursor] ) ? $this->tokens[$this->cursor++] : null;

		return $this->lookahead !== null;
	}

	/**
	 * @return null
	 */
	public function consume()
	{
		$peek = $this->peek();
		$this->peek = 0;

		return $peek;
	}

	/**
	 * @return null
	 */
	public function peek()
	{
		if ( isset( $this->tokens[$this->cursor + $this->peek] ) ) {
			return $this->tokens[$this->cursor + $this->peek++];
		}

		return null;
	}
} 