<?php

namespace Controllers;

class User extends \Controller {

	/**
	 * Регистрирует пользователя
	 * @param array $input
	 * @return array
	 * @throws \HttpException
	 */
	public function actionJoinPOST($input) {
		//Проверм входные данные
		if(!isset($input['login']) || !($login = trim($input['login']))) throw new \HttpException(400);
		if(!isset($input['password']) || !($password = trim($input['password']))) throw new \HttpException(400);

		//Хешируем пароль
		$hash = \Api::app()->auth->hashPassword($password);

		//Создаем пользователя
		$userId = \Api::app()->db->getRepository('Models\User')->createUser($login, $hash);

		//Если такой пользователь уже существует
		if(false === $userId) return ['error' => 'Login is busy'];

		return ['id' => $userId];
	}

}