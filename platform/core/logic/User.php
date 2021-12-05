<?php

namespace Peat;
class User extends BaseLogic
{
    private $addresses;

    public function __construct($user_id)
    {
        parent::__construct($this->getDB()->fetchUser($user_id));
        $this->id = $user_id;
        $this->type_name = 'user';

    }

    /**
     * Get the addresses for this user
     * @return array indexed holding address objects (stdClass)
     * @since 0.7.9
     */
    public function getAddresses() {
        if ($this->addresses) return $this->addresses;
        $addresses = $this->getDB()->fetchAddressesByUserId($this->getId());
        $this->addresses = $addresses;
        return $addresses;
    }

    /**
     * Overridden to include addresses in the output
     * @return \stdClass
     */
    public function completeRowForOutput(): void
    {
        $this->row->__addresses__ = $this->getAddresses();
        $this->row->slug = '__user__'; //the default slug...
    }

}

