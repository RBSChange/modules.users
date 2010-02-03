<?php
/**
 * @package modules.users
 */
class modules_users_tests_GroupServiceTest extends f_tests_AbstractBaseTest
{
	public function setUp()
    {
    	parent::setUp();
    	$this->truncateAllTables();
		$this->loadSQLResource("sql/UsersTest_create.sql");
		f_persistentdocument_PersistentProvider::getInstance()->setDocumentCache(false);
    }

    public function tearDown()
    {
    	parent::tearDown();
    }

	public function testGetDefaultGroup()
	{

		// Get Default backend Group
		$bgS = users_BackendgroupService::getInstance();
		$backendGroup = $bgS->getDefaultGroup();
		$this->assertType('users_persistentdocument_backendgroup', $backendGroup);
		$this->assertEquals($backendGroup->getId(), 10037);

		// Get Default frontend Group
		$fgS = users_FrontendgroupService::getInstance();
		$frontendGroup = $fgS->getDefaultGroup();
		$this->assertType('users_persistentdocument_frontendgroup', $frontendGroup);
		$this->assertEquals($frontendGroup->getId(), 10039);
	}

	public function testGetGroupByName()
	{
		$gS = users_GroupService::getInstance();

		$this->assertType('users_persistentdocument_backendgroup', $gS->getGroupByName('backend group') );

		$this->assertType('users_persistentdocument_frontendgroup', $gS->getGroupByName('frontend group') );
	}
}