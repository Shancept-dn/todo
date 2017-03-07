<?php

namespace Repositories;

use Doctrine\ORM\EntityRepository;

class Share extends EntityRepository {

	/**
	 * Сохранить данные расшаривания
	 * @param \Models\Share $share
	 */
	public function saveShare($share) {
		$this->getEntityManager()->persist($share);
		$this->getEntityManager()->flush();
	}

	/**
	 * Удалить расшаривание
	 * @param \Models\Share $share
	 * @return bool
	 */
	public function deleteShare($share) {
		$this->getEntityManager()->remove($share);
		$this->getEntityManager()->flush();
		return true;
	}

	/**
	 * Расшарить список
	 * @param \Models\Roster $roster
	 * @param \Models\User $user
	 * @param int|bool $readonly
	 * @return int
	 */
	public function addShare($roster, $user, $readonly) {
		//Создаем модель
		$share = new \Models\Share;
		$share->setRoster($roster);
		$share->setUser($user);
		$share->setReadonly($readonly);

		//Сохраняем в БД
		$this->getEntityManager()->persist($share);
		$this->getEntityManager()->flush();

		return $share->getId();
	}

	/**
	 * Возвращает какие списки пользователя кому расшарены
	 * @param int $userId
	 * @return array
	 */
	public function getUserShares($userId) {
		$dql = 'SELECT s,r,su FROM Models\Share s JOIN s.roster r JOIN r.user u JOIN s.user su WHERE u.id = ?1';

		return $this->getEntityManager()->createQuery($dql)
			->setParameter(1, $userId)
			->getArrayResult();
	}
}