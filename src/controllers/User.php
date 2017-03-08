<?php

namespace Controllers;

class User extends \Controller {

	/**
	 * Регистрирует пользователя
	 * @return array
	 * @throws \HttpException
	 */
	public function actionJoinPOST() {
		//Проверяем входные данные
		$login = $this->checkInputData('login');
		$password = $this->checkInputData('password');

		//Хешируем пароль
		$hash = \Api::app()->auth->hashPassword($password);

		//Создаем пользователя
		$userId = \Api::app()->db->getRepository('Models\User')->createUser($login, $hash);

		//Если такой пользователь уже существует
		if(false === $userId) return ['error' => 'Login is busy'];

		//Сбрасываем кэш поиска пользователей
		\Api::app()->cache->deleteByTag('user/search');

		return ['id' => $userId];
	}

	/**
	 * Ищет пользователей по подстроке логина
	 * @return array
	 * @throws \HttpException
	 */
	public function actionSearchGET() {
		//Проверяем аутентификацию пользователя
		if(!\Api::app()->auth->isSuccess())  throw new \HttpException(401);

		//Проверяем входные данные
		$query = $this->checkInputData('query');

		//Текущий пользователь
		$userId = \Api::app()->auth->getUser()->getId();

		//Проверяем наличие данных в кэше
		if(false === $allUsers = \Api::app()->cache->get('searchUserMatchLogin|'.$query)) {
			//Ищем всех пользователей по подстроке
			$allUsers = \Api::app()->db->getRepository('Models\User')->searchUserMatchLogin($query);

			//Сохраняем результат в кэш
			\Api::app()->cache->set('searchUserMatchLogin|'.$query, $allUsers, 'user/search');
		}

		//Фильтруем пользователей и отдаваемые данные
		$users = [];
		foreach($allUsers as $user) {
			if($user['id'] == $userId) continue; //Текущего пользователя пропускаем
			$users[] = [
				'id' => $user['id'],
				'login' => $user['login'],
			];
		}

		return $users;
	}

}