<?php

namespace Models;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity(repositoryClass="Repositories\User")
 * @Table(name="users")
 */
class User {
	/**
	 * @Id @GeneratedValue @Column(type="integer")
	 * @var int
	 */
	protected $id;
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $login;
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $password;
	/**
	 * @OneToMany(targetEntity="Roster", mappedBy="user")
	 * @var Roster[]
	 */
	protected $rosters = null;
	/**
	 * @OneToMany(targetEntity="Share", mappedBy="user")
	 * @var Share[]
	 */
	protected $shares = null;

	public function __construct() {
		$this->rosters = new ArrayCollection();
		$this->shares = new ArrayCollection();
	}

	public function getId() {
		return $this->id;
	}

	public function getLogin() {
		return $this->login;
	}

	public function setLogin($login) {
		$this->login = $login;
	}

	public function getPassword() {
		return $this->password;
	}

	public function setPassword($password) {
		$this->password = $password;
	}

	public function addRoster($roster) {
		$this->rosters[] = $roster;
	}

	public function addShare($share) {
		$this->shares[] = $share;
	}
}