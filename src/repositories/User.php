<?php

namespace Repositories;

use Doctrine\ORM\EntityRepository;

class User extends EntityRepository {

	/**
	 * Находит пользователя по логину
	 * @param string $login
	 * @return \Models\User|null
	 */
	public function getUserByLogin($login) {
		return $this->getEntityManager()
			->createQuery('SELECT u FROM Models\User u WHERE u.login = ?1')
			->setParameter(1, $login)
			->getOneOrNullResult();
	}

	/**
	 * Возвращает логины пользователей
	 * @return array
	 */
	public function getUsersLogin() {
		$dql = 'SELECT u.login FROM Models\User u';

		return $this->getEntityManager()->createQuery($dql)
			->getArrayResult();
	}
}