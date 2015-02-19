<?php

namespace Graviton\Rql\AST;

interface PropertyOperationInterface
{
    /**
     * @return string
     */
    public function getProperty();

    /**
     * @return mixed
     */
    public function getValue();
}