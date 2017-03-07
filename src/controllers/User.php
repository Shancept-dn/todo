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

		return ['id' => $userId];
	}

}