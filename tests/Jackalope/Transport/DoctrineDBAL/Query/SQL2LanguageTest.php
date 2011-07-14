<?php

namespace Jackalope\Transport\DoctrineDBAL\Query;

use Jackalope\Transport\DoctrineDBAL\DoctrineDBALTestCase;

class SQL2LanguageTest extends DoctrineDBALTestCase
{    
    public function assertValidJCRSQL($sql)
    {
        $query = $this->getMock('PHPCR\Query\QueryInterface');
        $query->expects($this->at(0))->method('getLanguage')->will($this->returnValue(\PHPCR\Query\QueryInterface::JCR_SQL2));
        $query->expects($this->at(1))->method('getStatement')->will($this->returnValue($sql));
        
        $parser = new SQL2Parser($query);
        $parser->parse();
    }
    
    public function assertInvalidJCRSQL($sql, $message)
    {
        $query = $this->getMock('PHPCR\Query\QueryInterface');
        $query->expects($this->at(0))->method('getLanguage')->will($this->returnValue(\PHPCR\Query\QueryInterface::JCR_SQL2));
        $query->expects($this->at(1))->method('getStatement')->will($this->returnValue($sql));
        
        $parser = new SQL2Parser($query);
        
        $this->setExpectedException('PHPCR\Query\InvalidQueryException', $message);
        $parser->parse();
    }
    
    public function assertASTEquals($sql, $expectedAST)
    {
        $query = $this->getMock('PHPCR\Query\QueryInterface');
        $query->expects($this->at(0))->method('getLanguage')->will($this->returnValue(\PHPCR\Query\QueryInterface::JCR_SQL2));
        $query->expects($this->at(1))->method('getStatement')->will($this->returnValue($sql));
        
        $parser = new SQL2Parser($query);
        $actualAST = $parser->getAST();
        
        $this->assertEquals($expectedAST, $actualAST);
    }
    
    public function testInvalidStart()
    {
        $this->assertInvalidJCRSQL('DELETE FROM', "[Syntax Error] line 0, col 0: Error: Expected SELECT, got 'DELETE'");
    }
    
    public function testDefaultQuery()
    {
        $this->assertValidJCRSQL('SELECT * FROM [nt:unstructured]');
    }
    
    public function testInvalidFrom()
    {
        $this->assertInvalidJCRSQL('SELECT FROM [nt:unstructured]', "[Syntax Error] line 0, col 7: Error: Expected * or identifier, got 'FROM'");
    }
    
    public function testASTDefaultQuery()
    {
        $this->assertASTEquals('SELECT * FROM [nt:unstructured]', array(
            'select' => array('*'),
            'from' => array(0 => array('type' => 'nt:unstructured', 'as' => null)),
            'where' => null
        ));
    }
    
    public function testQueryWithCondition()
    {
        $this->assertValidJCRSQL("SELECT *  FROM nt:base  WHERE jcr:path LIKE '/userenv/%'");
    }
    
    public function testQueryWithInvalidCondition()
    {
        $this->assertInvalidJCRSQL("SELECT *  FROM nt:base  WHERE (jcr:path = jcr:path", "[Syntax Error] line 0, col -1: Error: Expected Jackalope\Transport\DoctrineDBAL\Query\SQL2Lexer::T_CLOSE_PARENTHESIS, got end of string.");
    }
    
    public function testQueryWithOperators()
    {
        $this->assertValidJCRSQL("SELECT *  FROM nt:base  WHERE (jcr:path = jcr:path)");
        $this->assertValidJCRSQL("SELECT *  FROM nt:base  WHERE (jcr:path != jcr:path)");
        $this->assertValidJCRSQL("SELECT *  FROM nt:base  WHERE (jcr:path <> jcr:path)");
        $this->assertValidJCRSQL("SELECT *  FROM nt:base  WHERE (jcr:path >= jcr:path)");
        $this->assertValidJCRSQL("SELECT *  FROM nt:base  WHERE (jcr:path > jcr:path)");
        $this->assertValidJCRSQL("SELECT *  FROM nt:base  WHERE (jcr:path < jcr:path)");
        $this->assertValidJCRSQL("SELECT *  FROM nt:base  WHERE (jcr:path <= jcr:path)");
    }
    
    public function testQueryWithNestedConditions()
    {
        $this->assertValidJCRSQL("SELECT *  FROM nt:base WHERE jcr:path = jcr:path AND (foo = bar OR baz = boing)");
    }
    
    public function testQueryWithNot()
    {
        $this->assertValidJCRSQL("SELECT *  FROM nt:base WHERE NOT jcr:path = jcr:path");
    }
}