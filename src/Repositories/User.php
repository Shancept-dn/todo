<?php

namespace Repositories;

use Doctrine\ORM\EntityRepository;

class User extends EntityRepository {

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