<?php
/**
 * Created by PhpStorm.
 * User: arth
 * Date: 3/14/14
 * Time: 10:07 PM
 */

namespace OM\APIBundle\Helper\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Parser;

class UnixTimestamp extends FunctionNode
{
    private $arg;

    public function getSql(SqlWalker $sqlWalker)
    {
        return sprintf('UNIX_TIMESTAMP()');
    }

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}