<?php
/*
 * Your installation or use of this SugarCRM file is subject to the applicable
 * terms available at
 * http://support.sugarcrm.com/Resources/Master_Subscription_Agreements/.
 * If you do not agree to all of the applicable terms or do not have the
 * authority to bind the entity as an authorized representative, then do not
 * install or use this SugarCRM file.
 *
 * Copyright (C) SugarCRM Inc. All rights reserved.
 */

require_once 'include/SugarEmailAddress/SugarEmailAddress.php';

class SugarEmailAddressAddChangeTest extends Sugar_PHPUnit_Framework_TestCase
{

    protected $email;
    protected $old_email = 'test@sugar.example.com';
    protected $old_uuid;

    /**
     * Fetch a SugarEmailAddress for retrieval/checking purposes
     * @param $uuid - uuid (guid) of row to read in
     * @return SugarEmailAddress
     */
    protected function readSugarEmailAddress($uuid)
    {
        $sea = new SugarEmailAddress();
        $sea->disable_row_level_security = true; // SugarEmailAddress doesn't roll with security
        $sea->retrieve($uuid);
        return $sea;
    }

    protected function setUp()
    {
        SugarTestHelper::setUp('current_user');
        $this->email = SugarTestSugarEmailAddressUtilities::createEmailAddress($this->old_email);
        $this->old_uuid = SugarTestSugarEmailAddressUtilities::fetchEmailIdByAddress($this->old_email);
    }

    protected function tearDown()
    {
        SugarTestSugarEmailAddressUtilities::removeCreatedContactAndRelationships();
        SugarTestSugarEmailAddressUtilities::removeAllCreatedEmailAddresses();
        SugarTestHelper::tearDown();
    }

    /**
     * @group bug57426
     */
    public function testEmailAddressesBrandNew()
    {
        $new_address = 'test_george@sugar.example.com';

        // now change the email, keeping track of bean UUIDs
        $old_uuid = $this->old_uuid;
        $uuid = $this->email->AddUpdateEmailAddress($new_address);

        $this->assertNotNull($uuid, 'Failed to enter the new email in the database!');
        $this->assertNotNull($old_uuid, 'Not seeing the old email in the database!');
        $this->assertNotEquals($uuid, $old_uuid, 'Same Email Address Bean used for different Email Addresses!');

        $new_sea = $this->readSugarEmailAddress($uuid);
        $old_sea = $this->readSugarEmailAddress($old_uuid);
        $this->assertNotNull($new_sea, 'New Email Address not saved in database!');
        $this->assertEquals($this->old_email, $old_sea->email_address, 'Old Email Address was improperly Changed');
        $this->assertEquals($new_address, $new_sea->email_address, 'New Email Address was improperly saved!');

    }

    public function testEmailAddressesNoChange()
    {
        $uuid = $this->email->AddUpdateEmailAddress($this->old_email);

        $this->assertNotNull($uuid, 'Where did my email address go?');
        $this->assertEquals($this->old_uuid,$uuid, 'We are using a different bean for the same email address!');

        $sea = $this->readSugarEmailAddress($uuid);
        $this->assertNotNull($sea, 'We lost our Email Address row!');
        $this->assertEquals($this->old_email, $sea->email_address, 'Our Email Address Improperly Changed!');
    }

    public function testEmailAddressesChangeCaps()
    {
        $new_address = 'TEST@SUGAR.example.COM';
        // change the email with caps
        $old_uuid = $this->old_uuid;
        $uuid = $this->email->AddUpdateEmailAddress($new_address);

        $this->assertNotNull($uuid, 'Failed to enter the new email in the database!');
        $this->assertNotNull($old_uuid, 'Not seeing the old email in the database!');
        $this->assertEquals($uuid, $old_uuid, 'Different Email Address Bean used for same Email Address!');

        $new_sea = $this->readSugarEmailAddress($uuid);
        $this->assertNotNull($new_sea, 'Email Address not found in DB!');
        $this->assertEquals($new_address, $new_sea->email_address, 'Email Address in DB was not updated.');
    }

    public function testEmailSimulatedInvalidFlagWorkflow()
    {

        $workflow_email = 'testworkflow@sugar.example.com';
        $new_email = 'afreshnewemail@sugar.example.com';

        // simulate a before workflow: invalid is set to true
        $email_old_invalid = SugarTestSugarEmailAddressUtilities::createEmailAddress($workflow_email,'',array('invalid' => true));
        $old_uuid = SugarTestSugarEmailAddressUtilities::fetchEmailIdByAddress($workflow_email);
        $contact = SugarTestSugarEmailAddressUtilities::getContact();

        $email_old_invalid->stash($contact->id, $contact->module_dir);
        $email_old_invalid->AddUpdateEmailAddress($workflow_email,0); // 'workflow'

        $uuid = $email_old_invalid->AddUpdateEmailAddress($new_email,1,0,$old_uuid); // workflow is processed

        $this->assertNotNull($old_uuid, 'where is the old email address?');
        $this->assertNotNull($uuid, 'where is our new email address?');
        $this->assertNotEquals($old_uuid, $uuid, 'we used the same Email Address Bean for different Email Addresses!');

        // need a way to see our new work
        $fresh_sea = $this->readSugarEmailAddress($uuid);

        $this->assertNotNull($fresh_sea, 'Email Address not found in DB!');
        $this->assertEquals($new_email, $fresh_sea->email_address, 'Email Address in DB is not the same as expected.');
        $this->assertNotEquals(1, intval($fresh_sea->invalid_email), 'Workflow changes to Email not protected');
    }
}
