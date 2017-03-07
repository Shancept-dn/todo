<?php

namespace Repositories;

use Doctrine\ORM\EntityRepository;

class Roster extends EntityRepository {

	/**
	 * Переименовать список
	 * @param \Models\Roster $roster
	 * @param string $name
	 * @return bool
	 */
	public function renameRoster($roster, $name) {
		$roster->setName($name);
		$this->getEntityManager()->flush();
		return true;
	}

	/**
	 * Удалить список
	 * @param \Models\Roster $roster
	 * @return bool
	 */
	public function deleteRoster($roster) {
		$this->getEntityManager()->remove($roster);
		$this->getEntityManager()->flush();
		return true;
	}

	/**
	 * Создает новый список для пользователя
	 * @param \Models\User $user
	 * @param string $name
	 * @return int
	 */
	public function createRoster($user, $name) {
		//Создаем модель
		$roster = new \Models\Roster();
		$roster->setUser($user);
		$roster->setName($name);

		//Сохраняем в БД
		$this->getEntityManager()->persist($roster);
		$this->getEntityManager()->flush();

		return $roster->getId();
	}

	/**
	 * Возвращает списки пользоваля с данными о расшаривании
	 * @param int $userId
	 * @return array
	 */
	public function getUserRosters($userId) {
		$dql = 'SELECT r,i,s,su FROM Models\Roster r JOIN r.user u JOIN r.items i JOIN r.shares s JOIN s.user su WHERE u.id = ?1';

		return $this->getEntityManager()->createQuery($dql)
			->setParameter(1, $userId)
			->getArrayResult();
	}
}