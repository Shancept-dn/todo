<?php

namespace Models;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity @Table(name="rosters")
 */
class Roster {
	/**
	 * @Id @GeneratedValue @Column(type="integer")
	 * @var int
	 */
	protected $id;
	/**
	 * @ManyToOne(targetEntity="User", inversedBy="rosters")
	 */
	protected $user;
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;
	/**
	 * @OneToMany(targetEntity="Item", mappedBy="roster")
	 * @var Item[]
	 */
	protected $items = null;
	/**
	 * @OneToMany(targetEntity="Share", mappedBy="roster")
	 * @var Share[]
	 */
	protected $shares = null;

	public function __construct() {
		$this->items = new ArrayCollection();
		$this->shares = new ArrayCollection();
	}

	public function getId() {
		return $this->id;
	}

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function addItem($item) {
		$this->items[] = $item;
	}

	public function addShare($share) {
		$this->shares[] = $share;
	}

	public function setUser($user) {
		$user->addRoster($this);
		$this->user = $user;
	}

	public function getUser() {
		return $this->user;
	}
}