<?php
/**
 * @package modules.users
 */
class modules_users_tests_UserServiceTest extends f_tests_AbstractBaseTest
{


    public function prepareTestCase()
    {
    	RequestContext::clearInstance();
		RequestContext::getInstance('fr en')->setLang('fr');;
    }

	public function setUp()
	{
		parent::setUp();
		f_persistentdocument_PersistentProvider::getInstance()->setDocumentCache(false);
		$this->truncateAllTables();
		$this->loadSQLResource('sql/UsersTest_create.sql');

		include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'setup' . DIRECTORY_SEPARATOR . 'initData.php');
		$setup = new users_Setup();
		$setup->install();
	}

	protected function tearDown()
	{
		parent::tearDown();

		if (!is_null($this->provider))
		{
			$this->provider->reset();
		}
	}

	public function testGetUserByLogin()
	{

		$us = users_UserService::getInstance();
		$this->assertType('users_persistentdocument_user', $us->getBackEndUserByLogin('wwwadmin'));
		$this->assertNull($us->getBackEndUserByLogin('wwwadmin2'));

	}

	public function testCheckIdentity()
	{

		$us = users_UserService::getInstance();
		$user = $us->getBackEndUserByLogin('wwwadmin');

		$this->assertTrue($us->checkIdentity($user, 'grece$2004'));
		$this->assertFalse($us->checkIdentity($user, 'grece$2007'));

	}

	public function testLoginExist()
	{

		$us = users_UserService::getInstance();
		$this->assertTrue($us->getBackEndUserByLogin('wwwadmin') !== null);
		$this->assertFalse($us->getBackEndUserByLogin('wwwadmin2') === null);

	}

	public function testGeneratePassword()
	{

		$us = users_UserService::getInstance();
		$this->assertNotEmpty($us->generatePassword());
		$this->assertLength(10, $us->generatePassword());
		$this->assertLength(6, $us->generatePassword(6));
		$this->assertLength(20, $us->generatePassword(20));
		$this->assertRegExp('/[a-zA-Z0-9]+/', $us->generatePassword());

	}

	public function testResetPassword()
	{

		$us = users_UserService::getInstance();
		$user1 = $us->getBackEndUserByLogin('wwwadmin');
		$this->assertType('users_persistentdocument_user', $user1);

		$this->assertTrue($us->checkIdentity($user1, 'grece$2004'));
		$us->resetPassword($user1, 'test123456');
		$this->assertFalse($us->checkIdentity($user1, 'grece$2004'));
		$this->assertTrue($us->checkIdentity($user1, 'test123456'));
		$us->resetPassword($user1);
		$this->assertFalse($us->checkIdentity($user1, 'test123456'));

	}

	public function testCheckPassword()
	{

		$us = users_UserService::getInstance();
		$lowSecurity = "qsdvbui";
		$mediumSecurity = "5sdg6sf65";
		$highSecurity = "Eg52uF64iul";

		// Test default : security level = medium
		$this->assertFalse($us->checkPassword($lowSecurity));
		$this->assertTrue($us->checkPassword($mediumSecurity));
		$this->assertTrue($us->checkPassword($highSecurity));

		// Create a preference document to set the level security
		$preferenceDocument = ModuleService::getInstance()->getPreferencesDocument('users');

		// Test security level = low
		$preferenceDocument->setLabel('modules_users/preferences');
		$preferenceDocument->setSecuritylevel('low');
		$preferenceDocument->save();
		$this->assertTrue($us->checkPassword($lowSecurity));
		$this->assertTrue($us->checkPassword($mediumSecurity));
		$this->assertTrue($us->checkPassword($highSecurity));

		// Test security level = medium
		$preferenceDocument->setSecuritylevel('medium');
		$preferenceDocument->save();
		$this->assertFalse($us->checkPassword($lowSecurity));
		$this->assertTrue($us->checkPassword($mediumSecurity));
		$this->assertTrue($us->checkPassword($highSecurity));

		// Test security level = high
		$preferenceDocument->setSecuritylevel('high');
		$preferenceDocument->save();
		$this->assertFalse($us->checkPassword($lowSecurity));
		$this->assertFalse($us->checkPassword($mediumSecurity));
		$this->assertTrue($us->checkPassword($highSecurity));

	}

	public function testIsBackenduser()
	{
		$us = users_UserService::getInstance();
		$this->assertTrue( $us->isBackenduser( $us->getBackEndUserByLogin('wwwadmin')));
		$this->assertFalse( $us->isBackenduser( $us->getFrontendUserByLogin('intcoutl')));

	}


	public function testSendUserInformations()
	{
		$us = users_UserService::getInstance();
		$user = $us->getBackEndUserByLogin('wwwadmin');
		$this->assertTrue($us->sendUserInformations($user));
	}


	public function testCreateFrontendUser()
	{

		$fuS = users_FrontenduserService::getInstance();
		$frontendUser = $fuS->getNewDocumentInstance();
		$this->assertType('users_persistentdocument_frontenduser', $frontendUser);

		$frontendUser->setFirstname('toto');
		$frontendUser->setLastname('rbs');
		$frontendUser->setLogin('toto@rbs.fr');
		$frontendUser->setPassword('toto123');
		$frontendUser->save();

		$this->assertTrue( $fuS->checkIdentity($frontendUser, 'toto123') );

		$fgS = users_FrontendgroupService::getInstance();
		$defaultGroup = $fgS->getDefaultGroup();

		$this->assertNotEmpty($defaultGroup->getIndexofUsers($frontendUser));
	}

}
