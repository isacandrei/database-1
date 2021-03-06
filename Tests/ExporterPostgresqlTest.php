<?php
/**
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Database\Tests;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/ExporterPostgresqlInspector.php';

/**
 * Test the \Joomla\Database\Postgresql\PostgresqlExporter class.
 *
 * @since  1.0
 */
class ExporterPostgresqlTest extends TestCase
{
	/**
	 * @var    object  The mocked database object for use by test methods.
	 * @since  1.0
	 */
	protected $dbo = null;

	/**
	 * @var    string  The last query sent to the dbo setQuery method.
	 * @since  1.0
	 */
	protected $lastQuery = '';

	/**
	 * @var    bool  Boolean value to know if current database version is newer than 9.1.0
	 * @since  1.0
	 */
	private $ver9dot1 = true;

	/**
	 * Sets up the testing conditions
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function setup()
	{
		parent::setUp();

		// Set up the database object mock.
		$this->dbo = $this->getMockBuilder('Joomla\\Database\\Postgresql\\PostgresqlDriver')
			->disableOriginalConstructor()
			->setMethods(
				array(
					'getErrorNum',
					'getPrefix',
					'getTableColumns',
					'getTableKeys',
					'getTableSequences',
					'getVersion',
					'quoteName',
					'loadObjectList',
					'setQuery',
				)
			)
			->getMock();

		$this->dbo->expects(
			$this->any()
		)
		->method('getPrefix')
		->will(
			$this->returnValue(
				'jos_'
			)
		);

		$this->dbo->expects(
			$this->any()
		)
		->method('getTableColumns')
		->will(
			$this->returnValue(
				array(
					(object) array(
						'column_name' => 'id',
						'type' => 'integer',
						'null' => 'NO',
						'default' => 'nextval(\'jos_dbtest_id_seq\'::regclass)',
						'comments' => '',
					),
					(object) array(
						'column_name' => 'title',
						'type' => 'character varying(50)',
						'null' => 'NO',
						'default' => 'NULL',
						'comments' => '',
					),
					(object) array(
						'column_name' => 'start_date',
						'type' => 'timestamp without time zone',
						'null' => 'NO',
						'default' => 'NULL',
						'comments' => '',
					),
					(object) array(
						'column_name' => 'description',
						'type' => 'text',
						'null' => 'NO',
						'default' => 'NULL',
						'comments' => '',
					)
				)
			)
		);

		$this->dbo->expects(
			$this->any()
		)
		->method('getTableKeys')
		->will(
			$this->returnValue(
				array(
					(object) array(
						'idxName' => 'jos_dbtest_pkey',
						'isPrimary' => 'TRUE',
						'isUnique' => 'TRUE',
						'Query' => 'ALTER TABLE "jos_dbtest" ADD PRIMARY KEY (id)',
					)
				)
			)
		);

		/* Check if database is at least 9.1.0 */
		$this->dbo->expects(
			$this->any()
		)
		->method('getVersion')
		->will(
			$this->returnValue(
				'9.1.2'
			)
		);

		if (version_compare($this->dbo->getVersion(), '9.1.0') >= 0)
		{
			$this->ver9dot1 = true;
			$start_val = '1';
		}
		else
		{
			/* Older version */
			$this->ver9dot1 = false;
			$start_val = null;
		}

		$this->dbo->expects(
			$this->any()
		)
		->method('getTableSequences')
		->will(
			$this->returnValue(
				array(
					(object) array(
						'sequence' => 'jos_dbtest_id_seq',
						'schema' => 'public',
						'table' => 'jos_dbtest',
						'column' => 'id',
						'data_type' => 'bigint',
						'start_value' => $start_val,
						'minimum_value' => '1',
						'maximum_value' => '9223372036854775807',
						'increment' => '1',
						'cycle_option' => 'NO',
					)
				)
			)
		);

		$this->dbo->expects(
			$this->any()
		)
		->method('quoteName')
		->will(
			$this->returnCallback(
				array($this, 'callbackQuoteName')
			)
		);

		$this->dbo->expects(
			$this->any()
		)
		->method('setQuery')
		->will(
			$this->returnCallback(
				array($this, 'callbackSetQuery')
			)
		);

		$this->dbo->expects(
			$this->any()
		)
		->method('loadObjectList')
		->will(
			$this->returnCallback(
				array($this, 'callbackLoadObjectList')
			)
		);
	}

	/**
	 * Callback for the dbo loadObjectList method.
	 *
	 * @return array  An array of results based on the setting of the last query.
	 *
	 * @since  1.0
	 */
	public function callbackLoadObjectList()
	{
		return array();
	}

	/**
	 * Callback for the dbo quoteName method.
	 *
	 * @param   string  $value  The value to be quoted.
	 *
	 * @return string  The value passed wrapped in PostgreSQL quotes.
	 *
	 * @since  1.0
	 */
	public function callbackQuoteName($value)
	{
		return '"$value"';
	}

	/**
	 * Callback for the dbo setQuery method.
	 *
	 * @param   string  $query  The query.
	 *
	 * @return void
	 *
	 * @since  1.0
	 */
	public function callbackSetQuery($query)
	{
		$this->lastQuery = $query;
	}

	/**
	 * Test the magic __toString method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function test__toString()
	{
		$instance = new ExporterPostgresqlInspector;

		// Set up the export settings.
		$instance
			->setDbo($this->dbo)
			->from('jos_test')
			->withStructure(true);

		/* Depending on which version is running, 9.1.0 or older */
		$start_val = null;

		if ($this->ver9dot1)
		{
			$start_val = '1';
		}

		$expecting = '<?xml version="1.0"?>
<postgresqldump xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
 <database name="">
  <table_structure name="#__test">
   <sequence Name="jos_dbtest_id_seq" Schema="public" Table="jos_dbtest" Column="id" Type="bigint" Start_Value="' .
			$start_val . '" Min_Value="1" Max_Value="9223372036854775807" Increment="1" Cycle_option="NO" />
   <field Field="id" Type="integer" Null="NO" Default="nextval(\'jos_dbtest_id_seq\'::regclass)" Comments="" />
   <field Field="title" Type="character varying(50)" Null="NO" Default="NULL" Comments="" />
   <field Field="start_date" Type="timestamp without time zone" Null="NO" Default="NULL" Comments="" />
   <field Field="description" Type="text" Null="NO" Default="NULL" Comments="" />
   <key Index="jos_dbtest_pkey" is_primary="TRUE" is_unique="TRUE" Query="ALTER TABLE "jos_dbtest" ADD PRIMARY KEY (id)" />
  </table_structure>
 </database>
</postgresqldump>';

		// Replace used to prevent platform conflicts
		$this->assertThat(
			preg_replace('/\v/', '', (string) $instance),
			$this->equalTo(
				preg_replace('/\v/', '', $expecting)
			),
			'__toString has not returned the expected result.'
		);
	}

	/**
	 * Tests the asXml method.
	 *
	 * @return void
	 *
	 * @since  1.0
	 */
	public function testAsXml()
	{
		$instance = new ExporterPostgresqlInspector;

		$result = $instance->asXml();

		$this->assertThat(
			$result,
			$this->identicalTo($instance),
			'asXml must return an object to support chaining.'
		);

		$this->assertThat(
			$instance->asFormat,
			$this->equalTo('xml'),
			'The asXml method should set the protected asFormat property to "xml".'
		);
	}

	/**
	 * Test the buildXML method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testBuildXml()
	{
		$instance = new ExporterPostgresqlInspector;

		// Set up the export settings.
		$instance
			->setDbo($this->dbo)
			->from('jos_test')
			->withStructure(true);

		/* Depending on which version is running, 9.1.0 or older */
		$start_val = null;

		if ($this->ver9dot1)
		{
			$start_val = '1';
		}

		$expecting = '<?xml version="1.0"?>
<postgresqldump xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
 <database name="">
  <table_structure name="#__test">
   <sequence Name="jos_dbtest_id_seq" Schema="public" Table="jos_dbtest" Column="id" Type="bigint" Start_Value="' .
			$start_val . '" Min_Value="1" Max_Value="9223372036854775807" Increment="1" Cycle_option="NO" />
   <field Field="id" Type="integer" Null="NO" Default="nextval(\'jos_dbtest_id_seq\'::regclass)" Comments="" />
   <field Field="title" Type="character varying(50)" Null="NO" Default="NULL" Comments="" />
   <field Field="start_date" Type="timestamp without time zone" Null="NO" Default="NULL" Comments="" />
   <field Field="description" Type="text" Null="NO" Default="NULL" Comments="" />
   <key Index="jos_dbtest_pkey" is_primary="TRUE" is_unique="TRUE" Query="ALTER TABLE "jos_dbtest" ADD PRIMARY KEY (id)" />
  </table_structure>
 </database>
</postgresqldump>';

		// Replace used to prevent platform conflicts
		$this->assertThat(
			preg_replace('/\v/', '', $instance->buildXml()),
			$this->equalTo(
				preg_replace('/\v/', '', $expecting)
			),
			'buildXml has not returned the expected result.'
		);
	}

	/**
	 * Tests the buildXmlStructure method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testBuildXmlStructure()
	{
		$instance = new ExporterPostgresqlInspector;

		// Set up the export settings.
		$instance
			->setDbo($this->dbo)
			->from('jos_test')
			->withStructure(true);

		/* Depending on which version is running, 9.1.0 or older */
		$start_val = null;

		if ($this->ver9dot1)
		{
			$start_val = '1';
		}

		$this->assertThat(
			$instance->buildXmlStructure(),
			$this->equalTo(
				array(
					'  <table_structure name="#__test">',
					'   <sequence Name="jos_dbtest_id_seq" Schema="public" Table="jos_dbtest" Column="id" Type="bigint" Start_Value="' .
					$start_val . '" Min_Value="1" Max_Value="9223372036854775807" Increment="1" Cycle_option="NO" />',
					'   <field Field="id" Type="integer" Null="NO" Default="nextval(\'jos_dbtest_id_seq\'::regclass)" Comments="" />',
					'   <field Field="title" Type="character varying(50)" Null="NO" Default="NULL" Comments="" />',
					'   <field Field="start_date" Type="timestamp without time zone" Null="NO" Default="NULL" Comments="" />',
					'   <field Field="description" Type="text" Null="NO" Default="NULL" Comments="" />',
					'   <key Index="jos_dbtest_pkey" is_primary="TRUE" is_unique="TRUE" Query="ALTER TABLE "jos_dbtest" ADD PRIMARY KEY (id)" />',
					'  </table_structure>'
				)
			),
			'buildXmlStructure has not returned the expected result.'
		);
	}

	/**
	 * Tests the check method.
	 *
	 * @return void
	 *
	 * @since  1.0
	 */
	public function testCheckWithNoDbo()
	{
		$instance = new ExporterPostgresqlInspector;

		try
		{
			$instance->check();
		}
		catch (\Exception $e)
		{
			// Exception expected.
			return;
		}

		$this->fail(
			'Check method should throw exception if DBO not set'
		);
	}

	/**
	 * Tests the check method.
	 *
	 * @return void
	 *
	 * @since  1.0
	 */
	public function testCheckWithNoTables()
	{
		$instance	= new ExporterPostgresqlInspector;
		$instance->setDbo($this->dbo);

		try
		{
			$instance->check();
		}
		catch (\Exception $e)
		{
			// Exception expected.
			return;
		}

		$this->fail(
			'Check method should throw exception if DBO not set'
		);
	}

	/**
	 * Tests the check method.
	 *
	 * @return void
	 *
	 * @since  1.0
	 */
	public function testCheckWithGoodInput()
	{
		$instance	= new ExporterPostgresqlInspector;
		$instance->setDbo($this->dbo);
		$instance->from('foobar');

		try
		{
			$result = $instance->check();

			$this->assertThat(
				$result,
				$this->identicalTo($instance),
				'check must return an object to support chaining.'
			);
		}
		catch (\Exception $e)
		{
			$this->fail(
				'Check method should not throw exception with good setup: ' . $e->getMessage()
			);
		}
	}

	/**
	 * Tests the from method with bad input.
	 *
	 * @return void
	 *
	 * @since  1.0
	 */
	public function testFromWithBadInput()
	{
		$instance = new ExporterPostgresqlInspector;

		try
		{
			$instance->from(new \stdClass);
		}
		catch (\Exception $e)
		{
			// Exception expected.
			return;
		}

		$this->fail(
			'From method should thrown an exception if argument is not a string or array.'
		);
	}

	/**
	 * Tests the from method with expected good inputs.
	 *
	 * @return void
	 *
	 * @since  1.0
	 */
	public function testFromWithGoodInput()
	{
		$instance = new ExporterPostgresqlInspector;

		try
		{
			$result = $instance->from('jos_foobar');

			$this->assertThat(
				$result,
				$this->identicalTo($instance),
				'from must return an object to support chaining.'
			);

			$this->assertThat(
				$instance->from,
				$this->equalTo(array('jos_foobar')),
				'The from method should convert a string input to an array.'
			);
		}
		catch (\Exception $e)
		{
			$this->fail(
				'From method should not throw exception with good input: ' . $e->getMessage()
			);
		}
	}

	/**
	 * Tests the method getGenericTableName method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testGetGenericTableName()
	{
		$instance = new ExporterPostgresqlInspector;
		$instance->setDbo($this->dbo);

		$this->assertThat(
			$instance->getGenericTableName('jos_test'),
			$this->equalTo('#__test'),
			'The testGetGenericTableName should replace the database prefix with #__.'
		);
	}

	/**
	 * Tests the setDbo method with the wrong type of class.
	 *
	 * @return void
	 *
	 * @since  1.0
	 */
	public function testSetDboWithGoodInput()
	{
		$instance = new ExporterPostgresqlInspector;

		try
		{
			$result = $instance->setDbo($this->dbo);

			$this->assertThat(
				$result,
				$this->identicalTo($instance),
				'setDbo must return an object to support chaining.'
			);
		}
		catch (\PHPUnit_Framework_Error $e)
		{
			// Unknown error has occurred.
			$this->fail(
				$e->getMessage()
			);
		}
	}

	/**
	 * Tests the withStructure method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testWithStructure()
	{
		$instance = new ExporterPostgresqlInspector;

		$result = $instance->withStructure();

		$this->assertThat(
			$result,
			$this->identicalTo($instance),
			'withStructure must return an object to support chaining.'
		);

		$this->assertThat(
			$instance->options->withStructure,
			$this->isTrue(),
			'The default use of withStructure should result in true.'
		);

		$instance->withStructure(true);
		$this->assertThat(
			$instance->options->withStructure,
			$this->isTrue(),
			'The explicit use of withStructure with true should result in true.'
		);

		$instance->withStructure(false);
		$this->assertThat(
			$instance->options->withStructure,
			$this->isFalse(),
			'The explicit use of withStructure with false should result in false.'
		);
	}
}
