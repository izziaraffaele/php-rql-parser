<?php 
namespace Graviton\Rql\Visitor;

use Graviton\Rql\Visitor\VistorInterface;
use Graviton\Rql\AST;

/**
* Operation visitor
* It runs all the operation to a Querybuilder
*
* @author Izzia Raffaele
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*/
class OrchestrateVisitor implements VisitorInterface
{
    /**
     * map classes to querybuilder methods
     *
     * @var string<string>
     */
    private $queryMap = array(
        'Graviton\Rql\AST\EqOperation' => 'equal',
        'Graviton\Rql\AST\NeOperation' => 'notEqual',
        'Graviton\Rql\AST\LtOperation' => 'lt',
        'Graviton\Rql\AST\GtOperation' => 'gt',
        'Graviton\Rql\AST\LteOperation' => 'lte',
        'Graviton\Rql\AST\GteOperation' => 'gte',
        'Graviton\Rql\AST\InOperation' => 'in',
        'Graviton\Rql\AST\OutOperation' => 'out',
        'Graviton\Rql\AST\LikeOperation'=> 'like',
        'Graviton\Rql\AST\AndOperation' => 'orOperator',
        'Graviton\Rql\AST\OrOperation' => 'andOperator',
    );

    /**
     * Visit parsed operations and return Orchestrate query string
     * 
     * @param  AST\OperationInterface $operation Parsed operations
     * @return string                            Orchestrate query string
     */
    public function visit(AST\OperationInterface $operation)
    {
        if (in_array(get_class($operation), array_keys($this->queryMap))) {
            $method = $this->queryMap[get_class($operation)];
            return $this->$method($operation);
        }

        return '*'; // orchestrate select ALL
    }

    /**
     * Produces a "equal" verbatim query
     * 
     * @param  PropertyOperationInterface $operation [description]
     * @return string
     */
    public function equal(AST\PropertyOperationInterface $operation ){
        list( $key, $value ) = [$operation->getProperty(),$operation->getValue()];
        $this->addQueryPiece( "$key:`$value`" );
    }   

    /**
     * Produces a "not equal" verbatim query
     * 
     * @param  PropertyOperationInterface $operation [description]
     * @return string
     */
    public function notEqual(AST\PropertyOperationInterface $operation ){
        list( $key, $value ) = [$operation->getProperty(),$operation->getValue()];
        $this->addQueryPiece( "$key:-`$value`" );
    }

    /**
     * Produces a "equal" query with * wildcard
     * 
     * @param  PropertyOperationInterface $operation [description]
     * @return string
     */
    public function like(AST\PropertyOperationInterface $operation){
        list( $key, $value ) = [$operation->getProperty(),$operation->getValue()];
        $this->addQueryPiece( "$key:$value*" );
    }

    /**
     * Produces a "range" query
     * 
     * @param  PropertyOperationInterface $operation [description]
     * @return string
     */
    public function lt(AST\PropertyOperationInterface $operation ){
        list( $key, $value ) = [$operation->getProperty(),$operation->getValue()];
        $this->addQueryPiece("$key:(* TO $value)");
    }

     /**
     * Produces a "range" query
     * 
     * @param  PropertyOperationInterface $operation [description]
     * @return string
     */
    public function gt(AST\PropertyOperationInterface $operation ){
        list( $key, $value ) = [$operation->getProperty(),$operation->getValue()];
        $this->addQueryPiece("$key:($value TO *)");
    }

     /**
     * Produces a "range" query
     * 
     * @param  PropertyOperationInterface $operation [description]
     * @return string
     */
    public function lte(AST\PropertyOperationInterface $operation ){
        return $this->lt($operation);
    }

     /**
     * Produces a "range" query
     * 
     * @param  PropertyOperationInterface $operation [description]
     * @return string
     */
    public function gte(AST\PropertyOperationInterface $operation ){
        return $this->gt($operation);
    }

    /**
     * Produces a "in" query joining value with OR
     * 
     * @param  PropertyOperationInterface $operation [description]
     * @return string
     */
    public function in(AST\InOperation $operation){
        $key = $operation->getProperty();
        $values = [];

        foreach ($operation->getArray() as $value) {
            $values[] = "`$value`";
        }

        if( !is_string( $values ) )
        {
            $values = implode(' OR ',$values);
        }

        $this->addQueryPiece("$key:($values)");
    }

    /**
     * Produces a "not in" query joining value with OR
     * 
     * @param  PropertyOperationInterface $operation [description]
     * @return string
     */
    public function out(AST\outOperation $operation){
        $key = $operation->getProperty();
        $values = [];

        foreach ($operation->getArray() as $value) 
        {
            $values[] = "-`$value`";
        }

        if( !is_string( $values ) )
        {
            $values = implode(' OR ',$values);
        }

        $this->addQueryPiece("$key:($values)");
    }

    /**
     * Concatenates multiple queries with AND
     * 
     * @param  PropertyOperationInterface $operation [description]
     * @return string
     */
    public function andQuery( $operations ){
        $pieces = [];

        foreach ($operations as $query => $operation ) 
        {
            $pieces[] = $this->$query( $operation );
        }

        $this->addQueryPiece( '('.implode(' AND ',$pieces ).')' );
    }

    /**
     * Concatenates multiple queries with OR
     * 
     * @param  PropertyOperationInterface $operation [description]
     * @return string
     */
    public function orQuery( $operation){
        $pieces = [];

        foreach ($operations as $query => $operation ) 
        {
            $pieces[] = $this->$query( $operation );
        }

        $this->addQueryPiece( '('.implode(' OR ',$pieces ).')' );
    }

    protected function addQueryPiece($string){
        return $string;
    }
}