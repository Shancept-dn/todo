<?php

namespace Repositories;

use Doctrine\ORM\EntityRepository;

class User extends EntityRepository {

	/**
	 * Создает пользователя
	 * @param string $login
	 * @param string $password
	 * @return bool|int id пользователя или false - если такой пользователь уже существует
	 */
	public function createUser($login, $password) {
		//Проверяем есть ли пользователь с таким логином
		if(null !== $this->getUserByLogin($login)) return false;

		//Создаем модель пользователя
		$user = new \Models\User;
		$user->setLogin($login);
		$user->setPassword($password);

		//Сохраняем в БД
		$this->getEntityManager()->persist($user);
		$this->getEntityManager()->flush();

		return $user->getId();
	}

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

}