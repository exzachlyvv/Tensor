<?php

namespace Zephir\Optimizers\FunctionCall;

use Zephir\Call;
use Zephir\CompilationContext;
use Zephir\CompiledExpression;
use Zephir\HeadersManager;
use Zephir\Exception\CompilerException;
use Zephir\Optimizers\OptimizerAbstract;

class ArgminOptimizer extends OptimizerAbstract
{
    /**
     * @param mixed[] $expression
     * @param \Zephir\Call $call
     * @param \Zephir\CompilationContext $context
     * @throws \Zephir\Exception\CompilerException
     * @return \Zephir\CompiledExpression|bool
     */
    public function optimize(array $expression, Call $call, CompilationContext $context)
    {
        if (!isset($expression['parameters'])) {
            return false;
        }

        if (count($expression['parameters']) !== 1) {
            throw new CompilerException(
                'Argmin accepts exactly one argument, ' . count($expression['parameters']) . 'given.',
                $expression
            );
        }

        $call->processExpectedReturn($context);

        $symbolVariable = $call->getSymbolVariable();

        if ($symbolVariable->getType() !== 'variable') {
            throw new CompilerException(
                'Returned values by functions can only be assigned to variant variables.',
                $expression
            );
        }

        if ($call->mustInitSymbolVariable()) {
            $symbolVariable->initVariant($context);
        }

        $context->headersManager->add(
            'include/indexing',
            HeadersManager::POSITION_LAST
        );

        $resolvedParams = $call->getResolvedParams(
            $expression['parameters'],
            $context,
            $expression
        );

        $symbol = $context->backend->getVariableCode($symbolVariable);

        $context->codePrinter->output(
            'tensor_argmin(' . $symbol . ', ' . $resolvedParams[0] . ');'
        );

        return new CompiledExpression(
            'variable',
            $symbolVariable->getRealName(),
            $expression
        );
    }
}
