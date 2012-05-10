<?php

/**
 * LICENSE: Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * PHP version 5
 *
 * @category  Microsoft
 * @package   Tests\Unit\WindowsAzure\Core
 * @author    Abdelrahman Elogeel <Abdelrahman.Elogeel@microsoft.com>
 * @copyright 2012 Microsoft Corporation
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link      http://pear.php.net/package/azure-sdk-for-php
 */

namespace Tests\Unit\WindowsAzure\Core;
use WindowsAzure\Services\Core\ServiceRestProxy;
use WindowsAzure\Resources;
use WindowsAzure\Core\Http\HttpClient;
use WindowsAzure\Core\Http\Url;
use Tests\Mock\WindowsAzure\Core\Filters\SimpleFilterMock;
use WindowsAzure\Blob\Models\AccessCondition;
use WindowsAzure\Core\Serialization\XmlSerializer;

/**
 * Unit tests for class ServiceRestProxy
 *
 * @category  Microsoft
 * @package   Tests\Unit\WindowsAzure\Core
 * @author    Abdelrahman Elogeel <Abdelrahman.Elogeel@microsoft.com>
 * @copyright 2012 Microsoft Corporation
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/azure-sdk-for-php
 */
class ServiceRestProxyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers WindowsAzure\Services\Core\ServiceRestProxy::generateMetadataHeaders
     */
    public function test__construct()
    {
        // Setup
        $channel = new HttpClient();
        $uri     = 'http://www.microsoft.com';
        $accountName = 'myaccount';
        $dataSerializer = new XmlSerializer();
        
        // Test
        $proxy = new ServiceRestProxy($channel, $uri, $accountName, $dataSerializer);
        
        // Assert
        $this->assertNotNull($proxy);
        $this->assertEquals($accountName, $proxy->getAccountName());
        $this->assertEquals($uri, $proxy->getUri());
        
        return $proxy;
    }
    
    /**
     * @covers  WindowsAzure\Services\Core\ServiceRestProxy::withFilter
     * @depends test__construct
     */
    public function testWithFilter($restWrapper)
    {
        // Setup
        $filter = new SimpleFilterMock('name', 'value');
        
        // Test
        $actual = $restWrapper->withFilter($filter);
        
        // Assert
        $this->assertCount(1, $actual->getFilters());
        $this->assertCount(0, $restWrapper->getFilters());
    }
    
    /**
     * @covers  WindowsAzure\Services\Core\ServiceRestProxy::getFilters
     * @depends test__construct
     */
    public function testGetFilters($restWrapper)
    {
        // Setup
        $filter = new SimpleFilterMock('name', 'value');
        $withFilter = $restWrapper->withFilter($filter);
        
        // Test
        $actual1 = $withFilter->getFilters();
        $actual2 = $restWrapper->getFilters();
        
        // Assert
        $this->assertCount(1, $actual1);
        $this->assertCount(0, $actual2);
    }
    
    /**
     * @covers  WindowsAzure\Services\Core\ServiceRestProxy::addOptionalAccessConditionHeader
     * @depends test__construct
     */
    public function testAddOptionalAccessContitionHeader($restWrapper)
    {
        // Setup
        $expectedHeader = Resources::IF_MATCH;
        $expectedValue = '0x8CAFB82EFF70C46';
        $accessCondition = AccessCondition::ifMatch($expectedValue);
        $headers = array('Header1' => 'Value1', 'Header2' => 'Value2');
        
        // Test
        $actual = $restWrapper->addOptionalAccessConditionHeader($headers, $accessCondition);
        
        // Assert
        $this->assertCount(3, $actual);
        $this->assertEquals($expectedValue, $actual[$expectedHeader]);
    }
    
    /**
     * @covers  WindowsAzure\Services\Core\ServiceRestProxy::groupQueryValues
     * @depends test__construct
     */
    public function testGroupQueryValues($restWrapper)
    {
        // Setup
        $values = array('A', 'B', 'C');
        $expected = 'A,B,C';
        
        // Test
        $actual = $restWrapper->groupQueryValues($values);
        
        // Assert
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * @covers  WindowsAzure\Services\Core\ServiceRestProxy::groupQueryValues
     * @depends test__construct
     */
    public function testGroupQueryValuesWithNulls($restWrapper)
    {
        // Setup
        $values = array(null, '', null);
        
        // Test
        $actual = $restWrapper->groupQueryValues($values);
        
        // Assert
        $this->assertTrue(empty($actual));
    }
    
    /**
     * @covers  WindowsAzure\Services\Core\ServiceRestProxy::groupQueryValues
     * @depends test__construct
     */
    public function testGroupQueryValuesWithMix($restWrapper)
    {
        // Setup
        $values = array(null, 'B', 'C', '');
        $expected = 'B,C';
        
        // Test
        $actual = $restWrapper->groupQueryValues($values);
        
        // Assert
        $this->assertEquals($expected, $actual);
    }

    /** 
    * @covers WindowsAzure\Services\Core\ServiceRestProxy::addPostParameter
    * @depends test__construct
    */
    public function testPostParameter($restWrapper)
    {
        // Setup
        $postParameters = array();
        $key = 'a';
        $expected = 'b';
    
        // Test
        $processedPostParameters = $restWrapper->addPostParameter($postParameters, $key, $expected);
        $actual = $processedPostParameters[$key];

        // Assert
        $this->assertEquals(
            $expected,
            $actual
        );
    }
    
    /**
     * @covers WindowsAzure\Services\Core\ServiceRestProxy::generateMetadataHeaders
     * @depends test__construct
     */
    public function testGenerateMetadataHeader($proxy)
    {
        // Setup
        $metadata = array('key1' => 'value1', 'MyName' => 'WindowsAzure', 'MyCompany' => 'Microsoft_');
        $expected = array();
        foreach ($metadata as $key => $value) {
            $expected[Resources::X_MS_META_HEADER_PREFIX . strtolower($key)] = $value;
        }
        
        // Test
        $actual = $proxy->generateMetadataHeaders($metadata);
        
        // Assert
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * @covers WindowsAzure\Services\Core\ServiceRestProxy::generateMetadataHeaders
     * @depends test__construct
     */
    public function testGenerateMetadataHeaderInvalidNameFail($proxy)
    {
        // Setup
        $metadata = array('key1' => "value1\n", 'MyName' => "\rAzurr", 'MyCompany' => "Micr\r\nosoft_");
        $this->setExpectedException(get_class(new \InvalidArgumentException(Resources::INVALID_META_MSG)));
        
        // Test
        $proxy->generateMetadataHeaders($metadata);
    }
    
    /**
     * @covers WindowsAzure\Services\Core\ServiceRestProxy::getMetadataArray
     * @depends test__construct
     */
    public function testGetMetadataArray($proxy)
    {
        // Setup
        $expected = array('key1' => 'value1', 'myname' => 'azure', 'mycompany' => 'microsoft_');
        $metadataHeaders = array();
        foreach ($expected as $key => $value) {
            $metadataHeaders[Resources::X_MS_META_HEADER_PREFIX . strtolower($key)] = $value;
        }
        
        // Test
        $actual = $proxy->getMetadataArray($metadataHeaders);
        
        // Assert
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * @covers WindowsAzure\Services\Core\ServiceRestProxy::getMetadataArray
     * @depends test__construct
     */
    public function testGetMetadataArrayWithMsHeaders($proxy)
    {
        // Setup
        $key = 'name';
        $validMetadataKey = Resources::X_MS_META_HEADER_PREFIX . $key;
        $value = 'correct';
        $metadataHeaders = array('x-ms-key1' => 'value1', 'myname' => 'x-ms-date', 
                          $validMetadataKey => $value, 'mycompany' => 'microsoft_');
        
        // Test
        $actual = $proxy->getMetadataArray($metadataHeaders);
        
        // Assert
        $this->assertCount(1, $actual);
        $this->assertEquals($value, $actual[$key]);
    }
}

?>
