<?php

namespace LdapRecord\Tests\Models\ActiveDirectory;

use LdapRecord\Container;
use LdapRecord\Connection;
use LdapRecord\Tests\TestCase;
use LdapRecord\ConnectionException;
use LdapRecord\Models\Attributes\Password;
use LdapRecord\Models\ActiveDirectory\User;
use LdapRecord\Models\ActiveDirectory\Scopes\RejectComputerObjectClass;

class UserTest extends TestCase
{
    public function test_setting_password_requires_secure_connection()
    {
        Container::getInstance()->add(new Connection());

        $this->expectException(ConnectionException::class);

        new User(['unicodepwd' => 'password']);
    }

    public function test_changing_password_requires_secure_connection()
    {
        Container::getInstance()->add(new Connection());

        $this->expectException(ConnectionException::class);

        $user = (new User())->setRawAttributes(['dn' => 'foo']);
        $user->unicodepwd = ['old', 'new'];
    }

    public function test_set_password_on_new_user()
    {
        $user = new UserPasswordTestStub();
        $user->unicodepwd = 'foo';
        $this->assertEquals([Password::encode('foo')], $user->getModifications()[0]['values']);
    }

    public function test_password_mutator_alias_works()
    {
        $user = new UserPasswordTestStub(['password' => 'secret']);
        $this->assertEquals([Password::encode('secret')], $user->getModifications()[0]['values']);
    }

    public function test_changing_passwords()
    {
        $user = (new UserPasswordTestStub())->setRawAttributes(['dn' => 'foo']);
        $user->unicodepwd = ['bar', 'baz'];

        $this->assertEquals([
            [
                'attrib'  => 'unicodepwd',
                'modtype' => 2,
                'values'  => [Password::encode('bar')],
            ],
            [
                'attrib'  => 'unicodepwd',
                'modtype' => 1,
                'values'  => [Password::encode('baz')],
            ],
        ], $user->getModifications());
    }

    public function test_reject_computer_object_class_is_a_default_scope()
    {
        $this->assertInstanceOf(RejectComputerObjectClass::class, (new User())->getGlobalScopes()[RejectComputerObjectClass::class]);
    }

    public function test_scope_where_has_mailbox_is_applied()
    {
        Container::getInstance()->add(new Connection());

        $filters = User::whereHasMailbox()->filters;

        $this->assertEquals($filters['and'][4]['field'], 'msExchMailboxGuid');
        $this->assertEquals($filters['and'][4]['operator'], '*');
    }
}

class UserPasswordTestStub extends User
{
    protected function validateSecureConnection()
    {
        return true;
    }
}
