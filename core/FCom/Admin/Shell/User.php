<?php

/**
 * Class FCom_Admin_Shell_User
 *
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class FCom_Admin_Shell_User extends FCom_Shell_Action_Abstract
{
    static protected $_actionName = 'admin:user';

    static protected $_availOptions = [
        'u!' => 'username',
        'p?' => 'password',
        'e!' => 'email',
        'f!' => 'firstname',
        'l!' => 'lastname',
        'r!' => 'role',
        's?' => 'superadmin',
        'a?' => 'active',
        'y'  => 'yes',
    ];

    protected function _run()
    {
        $cmd = $this->getParam(2);
        if (!$cmd) {
            $this->println('{red*}ERROR:{/} No command specified.');
            return;
        }
        $method = '_' . $cmd . 'Cmd';
        if (!method_exists($this, $method)) {
            $this->println('{red*}ERROR:{/} Unknown command: {red*}' . $cmd . '{/}');
            return;
        }

        $this->{$method}();
    }

    protected function _getRoleId($r)
    {
        if (!$r) {
            return null;
        }
        $role = $this->FCom_Admin_Model_Role->orm()->where(['OR' => ['role_name' => $r, 'id' => $r]])->find_one();
        return $role ? $role->id() : null;
    }

    protected function _addCmd()
    {
        $username = $this->getOption('u');
        $email = $this->getOption('e');
        if (!$username) {
            $this->println('{red*}ERROR:{/} Missing user name');
            return;
        }
        if (!$email) {
            $this->println('{red*}ERROR:{/} Missing email');
            return;
        }
        if (!$this->BValidate->validateEmail($email)) {
            $this->println('{red*}ERROR:{/} Invalid email');
            return;
        }

        $userHlp = $this->FCom_Admin_Model_User;

        $user = $userHlp->orm()->where('username', $username)->find_one();
        if ($user) {
            $this->println('{red*}ERROR:{/} User with this username already exists');
            return;
        }
        $user = $userHlp->orm()->where('email', $email)->find_one();
        if ($user) {
            $this->println('{red*}ERROR:{/} User with this email already exists');
            return;
        }
        $password = $this->getOption('p');
        if (!$password || $password === true) {
            $this->out('New user password: ');
            $password = $this->FCom_Shell_Shell->stdin();
        }

        if (($role = $this->getOption('r'))) {
            $rId = $this->_getRoleId($role);
            if (!$rId) {
                $this->println('{red*}ERROR:{/} Invalid role name');
                return;
            }
        } else {
            $rId = null;
        }

        $data = [
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'firstname' => $this->getOption('f'),
            'lastname' => $this->getOption('l'),
            'role' => $rId,
            'is_superadmin' => (int)$this->getOption('s') ? 1 : 0,
            'status' => (int)$this->getOption('a') ? 'A' : 'I',
        ];

        $user = $userHlp->create($data)->save();

        $this->println('The user (ID: {green*}' . $user->id() . '{/}) has been added');
    }

    protected function _updateCmd()
    {
        $username = $this->getOption('u');
        $email = $this->getOption('e');
        if (!$username && !$email) {
            $this->println('{red*}ERROR:{/} Need username or email to find the user');
            return;
        }
        if ($email && !$this->BValidate->validateEmail($email)) {
            $this->println('{red*}ERROR:{/} Invalid email');
            return;
        }

        $userHlp = $this->FCom_Admin_Model_User;

        /** @var FCom_Admin_Model_User $user */
        $user = $userHlp->orm()->where(['OR' => ['username' => $username, 'email' => $email]])->find_one();
        if (!$user) {
            $this->println('{red*}ERROR:{/} User not found');
            return;
        }

        $password = $this->getOption('p');
        if ($password === true) {
            $this->out('Update password: ');
            $password = $this->FCom_Shell_Shell->stdin();
        }

        $data = [];
        if ($username && $user->get('username') !== $username) {
            $data['username'] = $username;
        }
        if ($email && $user->get('email') !== $email) {
            $data['email'] = $email;
        }
        if ($password) {
            $data['password'] = $password;
        }
        if (($f = $this->getOption('f')) && $user->get('firstname') !== $f) {
            $data['firstname'] = $f;
        }
        if (($l = $this->getOption('l')) && $user->get('lastname') !== $l) {
            $data['lastname'] = $l;
        }
        if (($r = $this->getOption('r')) !== null) {
            $rId = $this->_getRoleId($r);
            if (!$rId) {
                $this->println('{red*}ERROR:{/} Invalid role name');
                return;
            }
            if ($user->get('role_id') !== $rId) {
                $data['role_id'] = $rId;
            }
        }
        if (($s = $this->getOption('s')) !== null) {
            $s = (int)$s ? 1 : 0;
            if ($user->get('is_superadmin') != $s) {
                $data['is_superadmin'] = $s;
            }
        }
        if (($a = $this->getOption('a')) !== null) {
            $a = (int)$a ? 'A' : 'I';
            if ($user->get('status') !== $a) {
                $data['status'] = $a;
            }
        }
        if ($data) {
            $user->set($data)->save();
            $this->println('The user (ID: {green*}' . $user->id() . '{/}) has been updated');
        } else {
            $this->println('No changes made to the user (ID: {green*}' . $user->id() . '{/})');
        }
    }

    protected function _removeCmd()
    {
        $username = $this->getOption('u');
        $email = $this->getOption('e');
        if (!$username && !$email) {
            $this->println('{red*}ERROR:{/} Need username or email to find the user');
            return;
        }
        if ($email && !$this->BValidate->validateEmail($email)) {
            $this->println('{red*}ERROR:{/} Invalid email');
            return;
        }

        $userHlp = $this->FCom_Admin_Model_User;

        /** @var FCom_Admin_Model_User $user */
        $user = $userHlp->orm()->where(['OR' => ['username' => $username, 'email' => $email]])->find_one();
        if (!$user) {
            $this->println('{red*}ERROR:{/} User not found');
            return;
        }

        if (!$this->getOption('y')) {
            $this->out('Please type YES to confirm deletion of the user (ID: {green*}' . $user->id() . '{/}): ');
            $yes = $this->FCom_Shell_Shell->stdin();
            if (strtoupper($yes) !== "YES") {
                $this->println('User deletion canceled');
                return;
            }
        }

        $user->delete();

        $this->println('The user has been removed');
    }

    public function getShortHelp()
    {
        return 'Admin user management';
    }

    public function getLongHelp()
    {
        return <<<EOT

Admin user management.

Syntax: {white*}{$this->FCom_Shell_Shell->getParam(0)} admin:user {green*}<command> [parameters]{/}

Commands:

    {white*}add{/}      Add a user
    {white*}update{/}   Update a user (by email or username)
    {white*}remove{/}   Remove a user (by email or username)

Options:

    {white*}-u {green*}<username>{white*}
    --username={green*}<username>{/}       User name

    {white*}-p {green*}[password]{white*}
    --password={green*}[password]{/}       Password (if not supplied in parameter, will expect STDIN)

    {white*}-e {green*}<email>{white*}
    --email={green*}<email>{/}             Email

    {white*}-f {green*}<firstname>{white*}
    --firstname={green*}<firstname>{/}     First Name

    {white*}-l {green*}<lastname>{white*}
    --lastname={green*}<lastname>{/}       Last Name

    {white*}-r {green*}<role>{white*}
    --role={green*}<role>{/}               User role

    {white*}-s {green*}[superadmin]{white*}
    --superadmin={green*}[superadmin]{/}   Is this a super admin? (0 or 1, default: 1)

    {white*}-a {green*}[active]{white*}
    --active={green*}[active]{/}           Is this user active? (0 or 1, default: 1)

    {white*}-y
    --yes                       Answer YES for confirmations automatically

EOT;
    }
}