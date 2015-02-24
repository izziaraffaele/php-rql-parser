<?php

namespace Graviton\Rql\Queriable;

use Graviton\Rql\QueryInterface;
use SocalNick\Orchestrate\SearchOperation;

/**
 * QueryInterface
 * Just implement this interface, adding your business logic to each function
 * as needed in your specific use case - this will be called a "Queriable".
 * Then once implemented, construct your object and pass it to Query.applyToQueriable().
 * The query class will then call all applicable methods in the Query string to your Queriable.
 *
 * @category Graviton
 * @package  Rql
 * @author   Dario Nuevo <dario.nuevo@swisscom.com>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://swisscom.com
 */
class Orchestrate implements QueryInterface
{
    private $collection;
    private $query;
    private $limit;
    private $offset;
    private $sort;

    /**
     * Constructor; instanciate with a valid DocumentRepository instance
     *
     * @param DocumentRepository $repository repository
     */
    public function __construct( $collectionName )
    {
        $this->reset();

        $this->collection = $collectionName;

        if( empty( $this->collection ) )
        {
            throw new Exception( 'Invalid collection name' );
        }
    }

    /**
     * Executes the query
     *
     * @return mixed
     */
    public function execute()
    {
        if( trim( $this->query ) == '' )
        {
            $this->query = '*';
        }

        $searchOp = new SearchOperation( $this->collection, $this->query, $this->limit, $this->offset, $this->sort );

        $this->reset();
        return $searchOp;
    }

    /**
     * Apply "equal" condition; AND
     *
     * @param string $field Field name
     * @param mixed  $value Field value
     *
     * @return void
     */
    public function andEq($field, $value)
    {
        $value = $this->escapeValues( $value );

        $this->addQueryPiece("$field:$value");
        return $this;
    }

    /**
     * Apply "equal" condition; OR
     *
     * @param string $field Field name
     * @param mixed  $value Field value
     *
     * @return void
     */
    public function orEq($field, $value)
    {
        $value = $this->escapeValues( $value );

        $this->addQueryPiece("$field:$value", 'OR');
        return $this;
    }

    /**
     * Apply "not equal" condition; AND
     *
     * @param string $field Field name
     * @param mixed  $value Field value
     *
     * @return void
     */
    public function andNe($field, $value)
    {
        $value = $this->escapeValues( $value );

        $this->addQueryPiece("NOT $field:$value");
        return $this;
    }

    /**
     * Apply "not equal" condition; OR
     *
     * @param string $field Field name
     * @param mixed  $value Field value
     *
     * @return void
     */
    public function orNe($field, $value)
    {
        $value = $this->escapeValues( $value );

        $this->addQueryPiece("NOT $field:$value", 'OR');
        return $this;
    }

    /**
     * Apply "in" condition; AND
     *
     * @return void
     */
    public function andIn()
    {
        $values = func_get_args();
        $field = array_shift( $values );

        $values = $this->escapeValues( $values );

        $this->addQueryPiece("$field:(".implode(' OR ', $values ).')');
        return $this;
    }

    /**
     * Apply "or in" condition; OR
     *
     * @return void
     */
    public function orIn()
    {
        $values = func_get_args();
        $field = array_shift( $values );

        $values = $this->escapeValues( $values );

        $this->addQueryPiece("$field:(".implode(' OR ', $values ).')', 'OR');
        return $this;
    }

    /**
     * Apply "not in" condition; AND
     *
     * @return void
     */
    public function andOut()
    {
        $values = func_get_args();
        $field = array_shift( $values );

        $values = $this->escapeValues( $values );

        $this->addQueryPiece("NOT $field:(".implode(' OR', $values ).')');
        return $this;
    }

    /**
     * Apply "or not in" condition; OR
     *
     * @return void
     */
    public function orOut()
    {
        $values = func_get_args();
        $field = array_shift( $values );

        $values = $this->escapeValues( $values );

        $this->addQueryPiece("NOT $field:(".implode(' OR ', $values ).')', 'OR');
        return $this;
    }

    /**
     * Apply "greater then" condition; AND
     *
     * @param string $field Field name
     * @param mixed  $value Field value
     *
     * @return void
     */
    public function andGt($field, $value)
    {
        $value--;

        $this->addQueryPiece("$field:($value TO *)");
    }

    /**
     * Apply "greater then" condition; OR
     *
     * @param string $field Field name
     * @param mixed  $value Field value
     *
     * @return void
     */
    public function orGt($field, $value)
    {
        $value--;

        $this->addQueryPiece("$field:($value TO *)", 'OR');
    }

    /**
     * Apply "greater equals" condition; AND
     *
     * @param string $field Field name
     * @param mixed  $value Field value
     *
     * @return void
     */
    public function andGe($field, $value)
    {
        $this->addQueryPiece("$field:($value TO *)");
    }

    /**
     * Apply "greater equals" condition; OR
     *
     * @param string $field Field name
     * @param mixed  $value Field value
     *
     * @return void
     */
    public function orGe($field, $value)
    {
        $this->addQueryPiece("$field:($value TO *)", 'OR');
    }

    /**
     * Apply "less then" condition; AND
     *
     * @param string $field Field name
     * @param mixed  $value Field value
     *
     * @return void
     */
    public function andLt($field, $value)
    {
        $value--;
        $this->addQueryPiece("$field:(* TO $value)");
    }

    /**
     * Apply "less then" condition; OR
     *
     * @param string $field Field name
     * @param mixed  $value Field value
     *
     * @return void
     */
    public function orLt($field, $value)
    {
        $value--;
        $this->addQueryPiece("$field:(* TO $value)",'OR');
    }

    /**
     * Apply "less equals" condition; AND
     *
     * @param string $field Field name
     * @param mixed  $value Field value
     *
     * @return void
     */
    public function andLe($field, $value)
    {
        $this->addQueryPiece("$field:(* TO $value)");
    }

    /**
     * Apply "less equals" condition; OR
     *
     * @param string $field Field name
     * @param mixed  $value Field value
     *
     * @return void
     */
    public function orLe($field, $value)
    {
        $this->addQueryPiece("$field:(* TO $value)", 'OR');
    }

     /**
     * Apply "Limit" condition;
     *
     * @param string $field Field name
     * @param mixed  $value Field value
     *
     * @return void
     */
    public function limit($size)
    {
       $this->limit = $size;
    }

    /**
     * Apply "Limit" condition;
     *
     * @param string $field Field name
     * @param mixed  $value Field value
     *
     * @return void
     */
    public function offset($size)
    {
        if( !empty($size))
        {
            $this->offset = $size;
        }
    }

    /**
     * Apply "Limit" condition;
     *
     * @param string $field Field name
     * @param mixed  $value Field value
     *
     * @return void
     */
    public function sort($field, $order = 'asc')
    {
        $field = trim($field);
        $order = trim($order);

       $this->sort = "$field:$order";
    }

    /**
     * Add a piece to the query
     *
     * @param type $query
     * @param type $operator
     * @return type
     */
    protected function addQueryPiece( $query, $operator = 'AND' )
    {
        if( strpos( $query, 'id:' ) === 0 )
        {
            $query = preg_replace('/id:/', 'key:', $query, 1);
        }

        if( empty( $this->query ) )
        {
            $this->query = $query;
        }
        else
        {
            $this->query .= " $operator $query";
        }
    }

    protected function escapeValues( $values )
    {
        if( is_array( $values ) )
        {
            return array_map([$this,'escapeValues'],$values);
        }


        $template = ( in_array($values, ['false','true','null'] ) || strstr($values,'*') ) ? '%s' : '`%s`';

        return sprintf( $template, $values );
    }

    protected function reset()
    {
        $this->collection = '';
        $this->query = '';
        $this->limit = 100;
        $this->offset = 0;
        $this->sort = 'key:asc';
    }
}
