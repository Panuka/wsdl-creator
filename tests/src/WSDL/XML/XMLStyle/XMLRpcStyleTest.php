<?php
/**
 * Copyright (C) 2013-2016
 * Piotr Olaszewski <piotroo89@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
namespace Tests\WSDL\XML\XMLStyle;

use DOMDocument;
use Ouzo\Tests\Assert;
use PHPUnit_Framework_TestCase;
use WSDL\Builder\Parameter;
use WSDL\Parser\Node;
use WSDL\XML\XMLStyle\XMLRpcStyle;

/**
 * XMLRpcStyleTest
 *
 * @author Piotr Olaszewski <piotroo89@gmail.com>
 */
class XMLRpcStyleTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var DOMDocument
     */
    private $DOMDocument;
    /**
     * @var XMLRpcStyle
     */
    private $XMLRpcStyle;

    protected function setUp()
    {
        parent::setUp();
        $this->DOMDocument = new DOMDocument();
        $this->XMLRpcStyle = new XMLRpcStyle();
    }

    /**
     * @test
     */
    public function shouldGenerateDOMElementForBinding()
    {
        //when
        $DOMElement = $this->XMLRpcStyle->generateBinding($this->DOMDocument, 'soap');

        //then
        $this->assertEquals('soap:binding', $DOMElement->tagName);
        $this->assertEquals('rpc', $DOMElement->getAttribute('style'));
        $this->assertEquals('http://schemas.xmlsoap.org/soap/http', $DOMElement->getAttribute('transport'));
    }

    /**
     * @test
     */
    public function shouldGenerateDOMElementsForMessage()
    {
        //given
        $nodes = [
            new Node('int', '$age', false),
            new Node('object', '$user', false, [new Node('string', '$name', false)]),
            new Node('string', '$numbers', true)
        ];

        //when
        $DOMElements = $this->XMLRpcStyle->generateMessagePart($this->DOMDocument, $nodes);

        //then
        Assert::thatArray($DOMElements)->extracting('tagName')->containsExactly('part', 'part', 'part');

        $this->assertEquals('age', $DOMElements[0]->getAttribute('name'));
        $this->assertEquals('xsd:int', $DOMElements[0]->getAttribute('type'));

        $this->assertEquals('user', $DOMElements[1]->getAttribute('name'));
        $this->assertEquals('ns:User', $DOMElements[1]->getAttribute('element'));

        $this->assertEquals('numbers', $DOMElements[2]->getAttribute('name'));
        $this->assertEquals('ns:ArrayOfNumbers', $DOMElements[2]->getAttribute('type'));
    }

    /**
     * @test
     */
    public function shouldGenerateDOMElementsForTypesObject()
    {
        //given
        $parameters = [
            new Parameter(new Node('object', '$user', false, [new Node('string', '$name', false)]), false)
        ];

        //when
        $DOMElements = $this->XMLRpcStyle->generateTypes($this->DOMDocument, $parameters, 'soap');

        //then
        Assert::thatArray($DOMElements)
            ->extracting('tagName')
            ->containsExactly('xsd:element', 'xsd:complexType');

        //<xsd:element name="User" nillable="true" type="ns:User"/>
        $this->assertEquals('User', $DOMElements[0]->getAttribute('name'));
        $this->assertEquals('true', $DOMElements[0]->getAttribute('nillable'));
        $this->assertEquals('ns:User', $DOMElements[0]->getAttribute('type'));

        //<xsd:complexType name="User">
        //    <xsd:sequence>
        //        <xsd:element name="name" type="xsd:string"/>
        //    </xsd:sequence>
        //</xsd:complexType>
        $this->assertEquals('User', $DOMElements[1]->getAttribute('name'));
        $this->assertEquals('xsd:sequence', $DOMElements[1]->firstChild->tagName);
        $DOMElements1Nodes = $DOMElements[1]->firstChild->childNodes;
        Assert::thatArray(iterator_to_array($DOMElements1Nodes))
            ->extracting('tagName')
            ->containsExactly('xsd:element');
        $this->assertEquals('name', $DOMElements1Nodes[0]->getAttribute('name'));
        $this->assertEquals('xsd:string', $DOMElements1Nodes[0]->getAttribute('type'));
    }

    /**
     * @test
     */
    public function shouldGenerateDOMElementsForTypesArrayOdSimpleType()
    {
        //given
        $parameters = [
            new Parameter(new Node('string', '$numbers', true), true)
        ];

        //when
        $DOMElements = $this->XMLRpcStyle->generateTypes($this->DOMDocument, $parameters, 'soap');

        //then
        Assert::thatArray($DOMElements)
            ->extracting('tagName')
            ->containsExactly('xsd:complexType');

        //<xsd:complexType name="ArrayOfNumbers">
        //    <xsd:complexContent>
        //        <xsd:restriction base="soapenc:Array">
        //            <xsd:attribute ref="soapenc:arrayType" soap:arrayType="xsd:string[]"/>
        //        </xsd:restriction>
        //    </xsd:complexContent>
        //</xsd:complexType>
        $this->assertEquals('ArrayOfNumbers', $DOMElements[0]->getAttribute('name'));
        $this->assertEquals('xsd:complexContent', $DOMElements[0]->firstChild->tagName);
        $this->assertEquals('xsd:restriction', $DOMElements[0]->firstChild->firstChild->tagName);
        $this->assertEquals('soapenc:Array', $DOMElements[0]->firstChild->firstChild->getAttribute('base'));
        $this->assertEquals('xsd:attribute', $DOMElements[0]->firstChild->firstChild->firstChild->tagName);
        $this->assertEquals('soapenc:arrayType', $DOMElements[0]->firstChild->firstChild->firstChild->getAttribute('ref'));
        $this->assertEquals('xsd:string[]', $DOMElements[0]->firstChild->firstChild->firstChild->getAttribute('soap:arrayType'));
    }
}
