<?php

namespace Graviton\Rql\Visitor;

use Graviton\Rql\AST;
use Doctrine\ODM\MongoDB\Query\Builder as QueryBuilder;

/**
 * @author  List of contributors <https://github.com/libgraviton/php-rql-parser/graphs/contributors>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link    http://swisscom.ch
 */
class MongoOdm implements VisitorInterface
{
    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * map classes to querybuilder methods
     *
     * @var string<string>
     */
    private $propertyMap = array(
        'Graviton\Rql\AST\EqOperation' => 'equals',
        'Graviton\Rql\AST\NeOperation' => 'notEqual',
        'Graviton\Rql\AST\LtOperation' => 'lt',
        'Graviton\Rql\AST\GtOperation' => 'gt',
        'Graviton\Rql\AST\LteOperation' => 'lte',
        'Graviton\Rql\AST\GteOperation' => 'gte',
    );

    /**
     * map classes to array style methods of querybuilder
     *
     * @var string<string>
     */
    private $arrayMap = array(
        'Graviton\Rql\AST\InOperation' => 'in',
        'Graviton\Rql\AST\OutOperation' => 'notIn',
    );

    /**
     * map classes of query style operations to builder
     *
     * @var string<string>|bool
     */
    private $queryMap = array(
        'Graviton\Rql\AST\AndOperation' => 'addAnd',
        'Graviton\Rql\AST\OrOperation' => 'addOr',
        'Graviton\Rql\AST\QueryOperation' => false,
    );

    /**
     * map classes with an internal implementation to methods
     *
     * @var string<string>
     */
    private $internalMap = array(
        'Graviton\Rql\AST\SortOperation' => 'visitSort',
        'Graviton\Rql\AST\LimitOperation' => 'visitLimit',
        'Graviton\Rql\AST\LikeOperation' => 'visitLike',
    );

    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @return QueryBuilder
     */
    public function getBuilder()
    {
        return $this->queryBuilder;
    }

    public function visit(AST\OperationInterface $operation, $expr = false)
    {
        if (in_array(get_class($operation), array_keys($this->internalMap))) {
            $method = $this->internalMap[get_class($operation)];
            $this->$method($operation);

        } elseif ($operation instanceof AST\PropertyOperationInterface) {
            return $this->visitProperty($operation, $expr);

        } elseif ($operation instanceof AST\ArrayOperationInterface) {
            return $this->visitArray($operation, $expr);

        } elseif ($operation instanceof AST\QueryOperationInterface) {
            $method = $this->queryMap[get_class($operation)];
            return $this->visitQuery($method, $operation, $expr);
        }
    }

    protected function visitProperty(AST\PropertyOperationInterface $operation, $expr)
    {
        $method = $this->propertyMap[get_class($operation)];
        return $this->getField($operation->getProperty(), $expr)->$method($operation->getValue());
    }

    protected function visitArray(AST\ArrayOperationInterface $operation, $expr)
    {
        $method = $this->arrayMap[get_class($operation)];
        return $this->getField($operation->getProperty(), $expr)->$method($operation->getArray());
    }

    /**
     * @param string $field name of field to get
     * @param bool   $expr  should i wrap this in expr()
     */
    protected function getField($field, $expr)
    {
        if ($expr) {
            return $this->queryBuilder->expr()->field($field);
        }
        return $this->queryBuilder->field($field);
    }

    /**
     * @param string|boolean $addMethod name of method we will be calling or false if no method is needed
     */
    protected function visitQuery($addMethod, AST\QueryOperationInterface $operation, $expr = false)
    {
        $builder = $this->queryBuilder;
        if ($expr) {
            $builder = $this->queryBuilder->expr();
        }
        foreach ($operation->getQueries() as $query) {
            $expr = $this->visit($query, $addMethod !== false);
            if ($addMethod !== false) {
                $builder->$addMethod($expr);
            }
        }
        return $builder;
    }

    protected function visitSort(AST\SortOperationInterface $operation)
    {
        foreach ($operation->getFields() as $field) {
            $name = $field[0];
            $order = 'asc';
            if (!empty($field[1])) {
                $order = $field[1];
            }
            $this->queryBuilder->sort($name, $order);
        }
    }

    protected function visitLike(AST\LikeOperation $operation)
    {
        $regex = new \MongoRegex(sprintf('/%s/', str_replace('*', '.*', $operation->getValue())));
        $this->queryBuilder->field($operation->getProperty())->equals($regex);
    }

    protected function visitLimit(AST\LimitOperationInterface $operation)
    {
        $this->queryBuilder->limit($operation->getLimit())->skip($operation->getSkip());
    }
}
