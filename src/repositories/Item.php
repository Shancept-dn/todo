<?php

namespace Repositories;

use Doctrine\ORM\EntityRepository;

class Item extends EntityRepository {

	/**
	 * Создает дело в списке
	 * @param int $rosterId id списка дел
	 * @param string $text
	 * @return bool|int
	 */
	public function crateItem($rosterId, $text) {
		//Находим список
		if(null === $roster = $this->getEntityManager()->find('Models\Roster', $rosterId)) return false;

		//Создаем модель
		$item = new \Models\Item;
		$item->setRoster($roster);
		$item->setText($text);

		//Сохраняем в БД
		$this->getEntityManager()->persist($item);
		$this->getEntityManager()->flush();

		return $item->getId();
	}

	/**
	 * Удаляет дело из списка
	 * @param \Models\Item $item
	 * @return bool
	 */
	public function deleteItem($item) {
		$this->getEntityManager()->remove($item);
		$this->getEntityManager()->flush();
		return true;
	}


	/**
	 * Сохраняет изменения
	 * @param \Models\Item $item
	 * @return bool
	 */
	public function saveItem($item) {
		$this->getEntityManager()->persist($item);
		$this->getEntityManager()->flush();
		return true;
	}

	/**
	 * Возвращает элементы (дела) списка дел
	 * @param int $rosterId
	 * @return array
	 */
	public function getItems($rosterId) {
		$dql = 'SELECT i FROM Models\Item i JOIN i.roster r WHERE r.id = ?1';

		return $this->getEntityManager()->createQuery($dql)
			->setParameter(1, $rosterId)
			->getArrayResult();
	}
}