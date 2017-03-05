<?php

namespace Models;

/**
 * @Entity @Table(name="items")
 */
class Item {
	/**
	 * @Id @GeneratedValue @Column(type="integer")
	 * @var int
	 */
	protected $id;
	/**
	 * @ManyToOne(targetEntity="Roster", inversedBy="items")
	 */
	protected $roster;
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $text;

	public function getId() {
		return $this->id;
	}

	public function getText() {
		return $this->text;
	}

	public function setText($text) {
		$this->text = $text;
	}

	public function setRoster($roster) {
		$roster->addItem($this);
		$this->roster = $roster;
	}

	public function getRoster() {
		return $this->roster;
	}
}