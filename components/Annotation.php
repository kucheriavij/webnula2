<?php
/**
 * Annotation component - adapted from doctrine project, see license and authors.
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */

namespace webnula2\components;

use webnula2\common\Lexer;


/**
 * Class Annotation
 * @package webnula2\components
 */
final class Annotation extends \CApplicationComponent
{
	/**
	 * @var Lexer
	 */
	private $lexer;
	/**
	 * @var array
	 */
	private $namespaces = array();
	/**
	 * @var
	 */
	private $context;
	/**
	 * @var array
	 */
	private $identifiers = array( Lexer::ID_T, Lexer::TRUE_T, Lexer::FALSE_T );
	/**
	 * @var array
	 */
	private $ignoreGlobalNames = array(
		'access' => true, 'author' => true, 'copyright' => true, 'deprecated' => true,
		'example' => true, 'ignore' => true, 'internal' => true, 'link' => true, 'see' => true,
		'since' => true, 'tutorial' => true, 'version' => true, 'package' => true,
		'subpackage' => true, 'name' => true, 'global' => true, 'param' => true,
		'return' => true, 'staticvar' => true, 'category' => true, 'staticVar' => true,
		'static' => true, 'var' => true, 'throws' => true, 'inheritdoc' => true,
		'inheritDoc' => true, 'license' => true, 'todo' => true, 'deprecated' => true,
		'deprec' => true, 'author' => true, 'property' => true, 'method' => true,
		'abstract' => true, 'exception' => true, 'magic' => true, 'api' => true,
		'final' => true, 'filesource' => true, 'throw' => true, 'uses' => true,
		'usedby' => true, 'private' => true, 'Annotation' => true, 'override' => true,
		'codeCoverageIgnore' => true, 'codeCoverageIgnoreStart' => true, 'codeCoverageIgnoreEnd' => true,
		'Required' => true, 'Attribute' => true, 'Attributes' => true,
		'Target' => true, 'SuppressWarnings' => true,
	);

	/**
	 *
	 */
	public function init()
	{
		parent::init();

		$this->lexer = new Lexer();
	}

	/**
	 * @param $name
	 */
	public function addNamespace( $name )
	{
		$this->namespaces[] = $name;
	}

	/**
	 * @param array $namespaces
	 */
	public function setNamespaces( $namespaces )
	{
		foreach( $namespaces as $name ) {
			$this->namespaces[] = $name;
		}
	}

	/**
	 * @param \ReflectionClass $class
	 *
	 * @return array
	 */
	public function getClassAnnotations( \ReflectionClass $class )
	{
		$this->context = 'class ' . $class->getName();

		return $this->parse( $class->getDocComment() );
	}

	/**
	 * @param \ReflectionProperty $property
	 *
	 * @return array
	 */
	public function getPropertyAnnotations( \ReflectionProperty $property )
	{
		$this->context = 'property ' . $property->getDeclaringClass() . '::' . $property->getName();

		return $this->parse( $property->getDocComment() );
	}

	/**
	 * @param \ReflectionMethod $method
	 *
	 * @return array
	 */
	public function getMethodAnnotations( \ReflectionMethod $method )
	{
		$this->context = 'method ' . $method->getDeclaringClass() . '::' . $method->getName();

		return $this->parse( $method->getDocComment() );
	}

	/**
	 * @param $source
	 *
	 * @return array
	 */
	private function parse( $source )
	{
		$this->lexer->scan( $source );
		$this->lexer->next();

		return $this->ParseAnnotations();
	}

	/**
	 * @return array
	 */
	private function ParseAnnotations()
	{
		$annotations = array();
		while ( null !== $this->lexer->lookahead ) {
			if ( $this->lexer->lookahead['type'] !== Lexer::AT_T ) {
				$this->lexer->next();
				continue;
			}

			if ( null !== $this->lexer->token && $this->lexer->lookahead['offset'] === $this->lexer->token['offset'] + strlen( $this->lexer->token['value'] ) ) {
				$this->lexer->next();
				continue;
			}

			if ( ( null === ( $peek = $this->lexer->consume() )
					|| ( $peek['type'] !== Lexer::NS_T && !in_array( $peek['type'], $this->identifiers, true ) ) )
				|| ( $peek['offset'] !== $this->lexer->lookahead['offset'] + 1 )
			) {
				$this->lexer->next();
				continue;
			}

			if ( ( $annot = $this->ParseAnnotation() ) != null ) {
				$annotations[$annot[0]] = $annot[1];
			}
		}

		return $annotations;
	}

	/**
	 * @param $className
	 *
	 * @return bool
	 */
	private function classExists($className) {
		$className = str_replace('\\', '.', $className);
		if(($path = \Yii::getPathOfAlias($className)) !== false) {
			return is_file($path.'.php');
		}
		return false;
	}

	/**
	 * @param bool $nested
	 *
	 * @return mixed
	 */
	private function ParseAnnotation( $nested = false )
	{
		$this->expect( Lexer::AT_T );

		if ( $this->lexer->isAny( $this->identifiers ) ) {
			$this->lexer->next();
			$name = $this->lexer->token['value'];
		} else if ( $this->lexer->is( Lexer::NS_T ) ) {
			$name = '';
		}

		while ( $this->lexer->lookahead['offset'] === ( $this->lexer->token['offset'] + strlen( $this->lexer->token['value'] ) ) && $this->lexer->is( Lexer::NS_T ) ) {
			$this->lexer->next();
			$this->lexer->isAny( $this->identifiers );
			$name .= '\\' . $this->lexer->token['value'];
		}

		$found = false;
		$originalName = $name;

		if ( isset( $this->ignoreGlobalNames[$originalName] ) ) {
			return null;
		}

		if ( !empty( $this->namespaces ) ) {
			foreach ( $this->namespaces as $namespace ) {
				if ( $this->classExists( $namespace . '\\' . $name ) ) {
					$name = $namespace . '\\' . $name;
					$found = true;
					break;
				}
			}
		} else if ( $this->classExists( $name ) ) {
			$found = true;
		}

		$reflector = new \ReflectionClass( $name );
		if ( !$reflector->isSubclassOf( 'webnula2\common\Annotation' ) ) {
			return null;
		}

		$values = array();
		if ( $this->lexer->is( Lexer::OP_T ) ) {
			$this->expect( Lexer::OP_T );
			if ( !$this->lexer->is( Lexer::CP_T ) ) {
				$values = $this->ParseValues();
			}
			$this->expect( Lexer::CP_T );
		}

		$instance = new $name;
		foreach ( $values as $name => $value ) {
			$instance->$name = $value;
		}

		$instance->validate();

		if ( $nested ) {
			return $instance;
		} else {
			return array( $originalName, $instance );
		}
	}

	/**
	 * @param $token
	 */
	private function expect( $token )
	{
		if ( !$this->lexer->is( $token ) ) {
			$this->syntaxtError( $this->literal( $token ) );
		}

		$this->lexer->next();
	}

	/**
	 * @param int[] $tokens
	 *
	 * @throws \CException
	 */
	private function expectAny( $tokens )
	{
		if ( !$this->lexer->isAny( $tokens ) ) {
			$this->syntaxtError( implode(' or ', array_map(array($this->lexer, 'literal'), $tokens)) );
		}

		$this->lexer->next();
	}


	/**
	 * @param $expected
	 *
	 * @throws \CException
	 */
	private function syntaxtError( $expected )
	{
		$token = $this->lexer->lookahead;

		$message = "Expected {$expected}, got ";

		if ( $this->lexer->lookahead === null ) {
			$message .= 'end of string';
		} else {
			$message .= "'{$token['value']}' at position {$token['offset']}";
		}

		if ( $this->context ) {
			$message .= ' in ' . $this->context;
		}

		$message .= '.';

		throw new \CException( $message );
	}

	/**
	 * @param $token
	 *
	 * @return int|string
	 */
	private function literal( $token )
	{
		$ref = new \ReflectionClass( get_class( $this->lexer ) );
		foreach ( $ref->getConstants() as $name => $const ) {
			if ( $const === $token ) {
				return $name;
			}
		}

		return '';
	}

	/**
	 * @return array
	 */
	private function ParseValues()
	{
		$values = array();
		if ( $this->lexer->is( Lexer::OCB_T ) ) {
			$values['value'] = $this->ParseArray();

			return $values;
		}

		$values[] = $this->ParseValue();

		while ( $this->lexer->is( Lexer::CM_T ) ) {
			$this->expect( Lexer::CM_T );
			$token = $this->lexer->lookahead;
			$values[] = $this->ParseValue();
		}

		foreach ( $values as $k => $value ) {
			if ( is_object( $value ) && $value instanceof \stdClass ) {
				$values[$value->name] = $value->value;
			} else if ( !isset( $values['value'] ) ) {
				$values['value'] = $value;
			} else {
				if ( !is_array( $values['value'] ) ) {
					$values['value'] = array( $values['value'] );
				}

				$values['value'][] = $value;
			}

			unset( $values[$k] );
		}

		return $values;
	}

	/**
	 * @return array
	 */
	private function ParseArray()
	{
		$array = $values = array();

		$this->expect( Lexer::OCB_T );
		$values[] = $this->ParseElement();

		while ( $this->lexer->is( Lexer::CM_T ) ) {
			$this->expect( Lexer::CM_T );

			if ( $this->lexer->is( Lexer::CCB_T ) ) {
				break;
			}

			$values[] = $this->ParseElement();
		}

		$this->expect( Lexer::CCB_T );

		foreach ( $values as $value ) {
			list ( $key, $val ) = $value;

			if ( $key !== null ) {
				$array[$key] = $val;
			} else {
				$array[] = $val;
			}
		}

		return $array;
	}

	/**
	 * @return array
	 */
	private function ParseElement()
	{
		$peek = $this->lexer->consume();

		if ( Lexer::EQ_T === $peek['type'] || Lexer::CL_T === $peek['type'] ) {
			$this->expectAny( array( Lexer::INT_T, Lexer::STRING_T ) );

			$key = $this->lexer->token['value'];
			$this->expectAny( array( Lexer::EQ_T, Lexer::CL_T ) );

			return array( $key, $this->ParseLiteral() );
		}

		return array( null, $this->ParseValue() );
	}

	/**
	 * @return mixed
	 */
	private function ParseLiteral()
	{
		if ( $this->lexer->is( Lexer::OCB_T ) ) {
			return $this->ParseArray();
		}

		if ( $this->lexer->is( Lexer::AT_T ) ) {
			return $this->ParseAnnotation( true );
		}

		switch ( $this->lexer->lookahead['type'] ) {
			case Lexer::FLOAT_T:
				$this->expect( Lexer::FLOAT_T );

				return $this->lexer->token['value'];
			case Lexer::INT_T:
				$this->expect( Lexer::INT_T );

				return $this->lexer->token['value'];
			case Lexer::TRUE_T:
				$this->expect( Lexer::TRUE_T );

				return $this->lexer->token['value'];
			case Lexer::FALSE_T:
				$this->expect( Lexer::FALSE_T );

				return $this->lexer->token['value'];

			case Lexer::STRING_T:
				$this->expect( Lexer::STRING_T );

				return $this->lexer->token['value'];
			case Lexer::NULL_T:
				$this->expect( Lexer::NULL_T );

				return $this->lexer->token['value'];

			default:
				$this->expect( $this->lexer->lookahead['type'] );
				break;
		}
	}

	/**
	 * @return \stdClass
	 */
	private function ParseValue()
	{
		$peek = $this->lexer->consume();

		if ( $peek['type'] === Lexer::EQ_T ) {
			return $this->ParseAssign();
		}

		return $this->ParseLiteral();
	}

	/**
	 * @return \stdClass
	 */
	private function ParseAssign()
	{
		$this->expect( Lexer::ID_T );
		$name = $this->lexer->token['value'];

		$this->expect( Lexer::EQ_T );
		$field = new \stdClass();
		$field->name = $name;
		$field->value = $this->ParseLiteral();

		return $field;
	}
}