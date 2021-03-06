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

namespace Sugarcrm\SugarcrmTestsUnit\Security\Password\Backend;

use Sugarcrm\SugarcrmTestsUnit\TestReflection;
use Sugarcrm\Sugarcrm\Security\Password\Backend\Native;

/**
 *
 * @coversDefaultClass \Sugarcrm\Sugarcrm\Security\Password\Backend\Native
 *
 */
class NativeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Check that default is properly set and ensure that the defaults uses
     * BCRYPT on our test platform. If this is not the case we may need to
     * revisit some other tests.
     *
     * @coversNothing
     */
    public function testDefaultPassword()
    {
        $native = new Native();
        $this->assertSame(PASSWORD_DEFAULT, TestReflection::getProtectedValue($native, 'algo'));
        $this->assertEquals(PASSWORD_BCRYPT, PASSWORD_DEFAULT);
    }

    /**
     * @covers ::verify
     * @dataProvider providerTestVerify
     */
    public function testVerify($password, $hash, $expected)
    {
        $native = new Native();
        $this->assertSame($expected, $native->verify($password, $hash));
    }

    public function providerTestVerify()
    {
        return array(
            array(
                'password1',
                '$2y$10$duE5hc9IAC7JMBKxIZqXHu95QDpLtp1zk2SXjwZb9Sp2p0WDMCoSW',
                true,
            ),
            array(
                'password2',
                '$2y$10$duE5hc9IAC7JMBKxIZqXHu95QDpLtp1zk2SXjwZb9Sp2p0WDMCoSW',
                false,
            ),
        );
    }

    /**
     * @covers ::setOptions
     * @dataProvider providerTestSetOptions
     */
    public function testSetOptions(array $options, $expected)
    {
        $native = new Native();
        $native->setOptions($options);
        $this->assertEquals($expected, TestReflection::getProtectedValue($native, 'options'));
    }

    public function providerTestSetOptions()
    {
        return array(
            array(
                array('cost' => 10),
                array('cost' => 10),
            ),
            // salt should be removed
            array(
                array('cost' => 10, 'salt' => 'xyz'),
                array('cost' => 10),
            ),
            // salt should be removed
            array(
                array('salt' => 'xyz'),
                array(),
            ),
        );
    }

    /**
     * Hash testing with real salt using regex matching on the hash result
     *
     * @covers ::hash
     * @dataProvider providerTestHash
     */
    public function testHash($algo, array $options, $password, $pattern)
    {
        $native = new Native();
        $native->setAlgo($algo);
        $native->setOptions($options);

        $this->assertRegExp($pattern, $native->hash($password));
    }

    public function providerTestHash()
    {
        return array(
            array(
                'PASSWORD_BCRYPT',
                array(),
                'password1',
                '#^\$2y\$10+\$[./A-Za-z0-9]{53}$#D',
            ),
            array(
                'PASSWORD_BCRYPT',
                array('cost' => 5),
                'password2',
                '#^\$2y\$05+\$[./A-Za-z0-9]{53}$#D',
            ),
        );
    }

    /**
     * @covers ::needsRehash
     * @dataProvider providerTestNeedsRehash
     */
    public function testNeedsRehash($algo, array $options, $hash, $expected)
    {
        $native = new Native();
        $native->setAlgo($algo);
        $native->setOptions($options);
        $this->assertEquals($expected, $native->needsRehash($hash));
    }

    public function providerTestNeedsRehash()
    {
        return array(

            // BOGUS source
            array(
                'PASSWORD_BCRYPT',
                array(),
                'foobar',
                true,
            ),

            // EMPTY source
            array(
                'PASSWORD_BCRYPT',
                array(),
                '',
                true,
            ),

            // PASSWORD_BCRYPT source - different cost
            array(
                'PASSWORD_BCRYPT',
                array('cost' => 15),
                '$2y$10$duE5hc9IAC7JMBKxIZqXHu95QDpLtp1zk2SXjwZb9Sp2p0WDMCoSW',
                true,
            ),

            // PASSWORD_BCRYPT source - different encryption
            array(
                'PASSWORD_BCRYPT',
                array('cost' => 10),
                '$2x$10$duE5hc9IAC7JMBKxIZqXHu95QDpLtp1zk2SXjwZb9Sp2p0WDMCoSW',
                true,
            ),

            // PASSWORD_BCRYPT source - same cost
            array(
                'PASSWORD_BCRYPT',
                array('cost' => 10),
                '$2y$10$duE5hc9IAC7JMBKxIZqXHu95QDpLtp1zk2SXjwZb9Sp2p0WDMCoSW',
                false,
            ),

            // CRYPT_SHA256 source
            array(
                'PASSWORD_BCRYPT',
                array(),
                '$5$rounds=5000$1234567890123456$c5PoOfE/uqUoVcX5JnakJmrcR2VFEHZmQ.KaLEtUlR4',
                true,
            ),

            // CRYPT_SHA512 source
            array(
                'PASSWORD_BCRYPT',
                array(),
                '$6$rounds=5000$1234567890123456$QX1ndnRVi1/AxK0fPVQ4ZIQO.ThxS5VmQptu8AgQcjMCkETlLRDh4geJNhMtGvTWdQc.pFQ3l.TCeG/yvbukG.',
                true,
            ),
        );
    }
}
