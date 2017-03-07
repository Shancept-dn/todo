<?php

namespace Models;

/**
 * @Entity(repositoryClass="Repositories\Item")
 * @Table(name="items")
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
	/**
	 * @Column(type="integer")
	 * @var integer
	 */
	protected $done = 0;

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

	public function getDone() {
		return $this->done;
	}

	public function setDone($done) {
		$this->done = $done;
	}
}