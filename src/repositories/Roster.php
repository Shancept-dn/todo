<?php

namespace Repositories;

use Doctrine\ORM\EntityRepository;

class Roster extends EntityRepository {

	/**
	 * Проверяет может ли пользователь редактировать список
	 * @param \Models\Roster $roster
	 * @param int $userId
	 * @return bool
	 */
	public function checkRosterAllowEditing($roster, $userId) {
		//Если список принадлежит пользователю
		if($roster->getUser()->getId() === $userId) return true;

		//Проверяем расшарен ли список пользовалю
		foreach($roster->getShares() as $share) {
			if($share->getUser()->getId() !== $userId) continue;
			if(!$share->getReadonly()) return true;
		}

		return false;
	}

	/**
	 * Проверяет может ли пользователь смотреть список
	 * @param \Models\Roster $roster
	 * @param int $userId
	 * @return bool
	 */
	public function checkRosterAllowReading($roster, $userId) {
		//Если список принадлежит пользователю
		if($roster->getUser()->getId() === $userId) return true;

		//Проверяем расшарен ли список пользовалю
		foreach($roster->getShares() as $share) {
			if($share->getUser()->getId() === $userId) return true;
		}

		return false;
	}

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
	 * Возвращает списки, доступные пользователю (собственные и расшаренные)
	 * @param int $userId
	 * @return array
	 */
	public function getUserAllowedRosters($userId) {
		$dql = 	'SELECT r FROM Models\Roster r '.
				'JOIN r.user ru LEFT JOIN r.shares rs '.
				'LEFT JOIN rs.user rsu '.
				'WHERE ru.id = ?1 OR rsu.id = ?1';

		return $this->getEntityManager()->createQuery($dql)
			->setParameter(1, $userId)
			->getArrayResult();
	}
}