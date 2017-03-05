<?php

namespace Models;

/**
 * @Entity(repositoryClass="Repositories\Share")
 * @Table(name="shares")
 */
class Share {
	/**
	 * @Id @GeneratedValue @Column(type="integer")
	 * @var int
	 */
	protected $id;
	/**
	 * @ManyToOne(targetEntity="Roster", inversedBy="shares")
	 */
	protected $roster;
	/**
	 * @ManyToOne(targetEntity="User", inversedBy="shares")
	 */
	protected $user;
	/**
	 * @Column(type="integer")
	 * @var integer
	 */
	protected $readonly;

	public function __construct() {
		$this->readonly = 1;
	}

	public function getId() {
		return $this->id;
	}

	public function getReadonly() {
		return $this->readonly;
	}

	public function setReadonly($readonly) {
		$this->readonly = (int)$readonly;
	}

	public function setRoster($roster) {
		$roster->addShare($this);
		$this->roster = $roster;
	}

	public function getRoster() {
		return $this->roster;
	}

	public function setUser($user) {
		$user->addShare($this);
		$this->user = $user;
	}

	public function getUser() {
		return $this->user;
	}
}