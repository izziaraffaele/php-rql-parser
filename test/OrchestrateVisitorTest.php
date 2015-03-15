<?php

namespace Graviton\Rql;

use Graviton\Rql\AST;
use Graviton\Rql\Parser;
use Graviton\Rql\Parser\Strategy;
use Graviton\Rql\Visitor\OrchestrateVisitor;

/**
 * @author  Izzia Raffaele
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */
class OrchestrateVisitorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider basicQueryProvider
     *
     * @param string  $query    rql query string
     * @param array[] $expected structure of expected return value
     * @param boolean $skip     skip test
     *
     * @return void
     */
    public function testBasicQueries($query, $expected, $skip = false)
    {
        if ($skip) {
            $this->markTestSkipped(sprintf('Please unskip the test when you add support for %s', $query));
        }

        $parser = Parser::createParser($query);
        $visitor = new OrchestrateVisitor;
        $ast = $parser->getAST();
        $results = '';


        if ($ast != null) {
            $result = $visitor->visit( $ast );
        }

        $this->assertEquals($expected, $results);
    }

    /**
     * @return array<string>
     */
    public function basicQueryProvider()
    {
        return array(
            'single equal query' => array(
                'eq(id,my-resource-id)', 'id:`my-resource-id`'
            ),
            'single not equal query' => array(
                'ne(id,my-resource-id)', 'id:-`my-resource-id`'
            ),
            'single array query' => array(
                'in(id,[my-resource-id,my-other-resource-id])', 'id:(`my-resource-id` OR `my-other-resource-id`)'
            ),
            'Or search' => array(
                'or(eq(id,my-resource-id),like(name,Resource name))', '(id:`my-resource-id` OR name:Resource name*)'
            ),
            'Nested operators' => array(
                'or(eq(id,my-resource-id),and(eq(gender,male),like(name,Resource name)))', '(id:`my-resource-id` OR (gender:`male` AND name:Resource name*))'
            ),
        );
    }
}
